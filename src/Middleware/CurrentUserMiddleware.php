<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Core\Configure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CurrentUser middleware
 *
 * Authentication が request に付与する identity から、
 * リクエスト中だけ参照できる CurrentUser 情報を Configure に置く。
 */
class CurrentUserMiddleware implements MiddlewareInterface
{
    private const KEY_ID = 'Auth.User.id';
    private const KEY_ROLE_ID = 'Auth.User.role_id';
    private const KEY_DISPLAY_NAME = 'Auth.User.display_name';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identity = $request->getAttribute('identity');

        // 未認証/CLI等で identity が無い場合は、書き込まずにそのまま流す
        if ($identity === null) {
            return $handler->handle($request);
        }

        // 取得（可能な範囲で）
        $userId = null;
        $roleId = null;
        $displayName = null;

        try {
            // getIdentifier() は通常 id（主キー）
            if (method_exists($identity, 'getIdentifier')) {
                $v = $identity->getIdentifier();
                if (is_scalar($v) || (is_object($v) && method_exists($v, '__toString'))) {
                    $userId = (string)$v;
                }
            }

            // IdentityInterface 実装なら get() がある想定（なければ無視）
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
            // ここで落として認証済みリクエスト全体を壊さない
            $userId = null;
            $roleId = null;
            $displayName = null;
        }

        // リクエスト中だけ参照できるようにセット
        Configure::write(self::KEY_ID, $userId);
        Configure::write(self::KEY_ROLE_ID, $roleId);
        Configure::write(self::KEY_DISPLAY_NAME, $displayName);

        try {
            return $handler->handle($request);
        } finally {
            // 長寿命プロセス対策：必ず掃除
            Configure::delete(self::KEY_ID);
            Configure::delete(self::KEY_ROLE_ID);
            Configure::delete(self::KEY_DISPLAY_NAME);
        }
    }
}
