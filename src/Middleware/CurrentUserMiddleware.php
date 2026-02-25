<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CurrentUser middleware
 *
 * Authentication が request に付与する identity から、
 * リクエストスコープの CurrentUser 情報を Request Attribute として伝播させる。
 *
 * ## 変更理由（Configure からの移行）
 *
 * 以前は Configure::write('Auth.User.*') でグローバルに書き込んでいたが、
 * Configure はアプリケーション全体の設定領域であり、
 * リクエストスコープのデータを置く場所として適切ではない。
 * 将来的な非同期・並列処理環境（RoadRunner 等）では値の混入リスクがあるため、
 * Request Attribute として request オブジェクト自体に乗せる方式に変更した。
 *
 * ## 参照方法
 *
 * Controller:
 *   $currentUser = $this->request->getAttribute('currentUser');
 *   $userId = $currentUser['id'] ?? null;
 *
 * UserTrackingBehavior / その他サービス:
 *   Configure::read('Auth.User.id') の代わりに、
 *   イベントの $options 経由か、サービスへの明示的な引数渡しで対応する。
 *
 * ## 後方互換（移行期間中）
 *
 * UserTrackingBehavior が Configure::read() に依存しているため、
 * 暫定的に Configure にも書き込む（二重書き込み）。
 * UserTrackingBehavior の移行完了後に Configure 書き込みを削除すること。
 */
class CurrentUserMiddleware implements MiddlewareInterface
{
    /** @var string Request Attribute のキー名 */
    public const ATTRIBUTE = 'currentUser';

    // 暫定的な後方互換キー（UserTrackingBehavior 移行後に削除）
    private const LEGACY_KEY_ID           = 'Auth.User.id';
    private const LEGACY_KEY_ROLE_ID      = 'Auth.User.role_id';
    private const LEGACY_KEY_DISPLAY_NAME = 'Auth.User.display_name';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identity = $request->getAttribute('identity');

        if ($identity === null) {
            return $handler->handle($request);
        }

        ['id' => $userId, 'role_id' => $roleId, 'display_name' => $displayName]
            = $this->extractUserData($identity);

        // Request Attribute として伝播（スコープはこのリクエストのみ）
        $request = $request->withAttribute(self::ATTRIBUTE, [
            'id'           => $userId,
            'role_id'      => $roleId,
            'display_name' => $displayName,
        ]);

        // 後方互換: UserTrackingBehavior が Configure::read() を使っているため暫定で残す
        // TODO: UserTrackingBehavior を Request Attribute ベースに移行したら削除
        \Cake\Core\Configure::write(self::LEGACY_KEY_ID, $userId);
        \Cake\Core\Configure::write(self::LEGACY_KEY_ROLE_ID, $roleId);
        \Cake\Core\Configure::write(self::LEGACY_KEY_DISPLAY_NAME, $displayName);

        try {
            return $handler->handle($request);
        } finally {
            // 長寿命プロセス対策: 必ず掃除
            \Cake\Core\Configure::delete(self::LEGACY_KEY_ID);
            \Cake\Core\Configure::delete(self::LEGACY_KEY_ROLE_ID);
            \Cake\Core\Configure::delete(self::LEGACY_KEY_DISPLAY_NAME);
        }
    }

    /**
     * identity から userId / roleId / displayName を安全に抽出する。
     *
     * @return array{id: string|null, role_id: string|null, display_name: string|null}
     */
    private function extractUserData(mixed $identity): array
    {
        $userId      = null;
        $roleId      = null;
        $displayName = null;

        try {
            if (method_exists($identity, 'getIdentifier')) {
                $v = $identity->getIdentifier();
                if (is_scalar($v) || (is_object($v) && method_exists($v, '__toString'))) {
                    $userId = (string)$v;
                }
            }

            if (method_exists($identity, 'get')) {
                $r = $identity->get('role_id');
                if (is_scalar($r) || (is_object($r) && method_exists($r, '__toString'))) {
                    $roleId = (string)$r;
                }

                $d = $identity->get('display_name');
                if (is_scalar($d) || (is_object($d) && method_exists($d, '__toString'))) {
                    $displayName = (string)$d;
                }
            }
        } catch (\Throwable) {
            // identity の不正な実装で落とさない
        }

        return ['id' => $userId, 'role_id' => $roleId, 'display_name' => $displayName];
    }
}
