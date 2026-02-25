<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\CybozuOAuthService;
use App\Service\KintoneApiClient;
use App\Service\KintoneWhoAmIService;
use Cake\Event\EventInterface;
use RuntimeException;

/**
 * Cybozu OAuth 連携コントローラー
 *
 * /auth/cybozu/connect   → 有効なトークンがあればスキップ、なければ認可URLへリダイレクト
 * /auth/cybozu/callback  → code受け取り → token取得 → ログイン名照合 → DB保存
 * /auth/cybozu/revoke    → 連携解除
 *
 * ## CakePHP 5 のDI注入方法
 *
 * CakePHP 5 ではコントローラへのサービス注入は「アクションメソッドの引数」で行う。
 * コンストラクタ注入・initialize() 内での getContainer() は使用不可。
 * Application::services() にサービスを登録しておくことで、
 * フレームワークがアクション呼び出し時に自動的にインスタンスを注入する。
 */
class CybozuController extends AppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
    }

    /**
     * Step 1: 有効なトークンが既にあればスキップ、なければ cybozu 認可画面へリダイレクト
     *
     * GET /auth/cybozu/connect
     */
    public function connect(CybozuOAuthService $cybozuService): void
    {
        $this->request->allowMethod(['get']);

        $currentUserId = (string)$this->Authentication->getIdentity()->getIdentifier();

        $token = $cybozuService->getValidToken($currentUserId);
        if ($token !== null) {
            $this->Flash->success(__('Cybozu account is already linked.'));
            $this->redirect($this->referer(['controller' => 'Users', 'action' => 'view', $currentUserId]));

            return;
        }

        $returnUrl = $this->referer(['controller' => 'Pages', 'action' => 'display', 'home'], true);
        $this->request->getSession()->write('Cybozu.return_url', $returnUrl);

        $state = bin2hex(random_bytes(16));
        $this->request->getSession()->write('Cybozu.oauth_state', $state);

        $this->redirect($cybozuService->buildAuthorizationUrl($state));
    }

    /**
     * Step 2: cybozu からのコールバック
     *
     * GET /auth/cybozu/callback?code=xxx&state=yyy
     */
    public function callback(CybozuOAuthService $cybozuService, KintoneWhoAmIService $whoAmIService): void
    {
        $this->request->allowMethod(['get']);

        $receivedState = (string)$this->request->getQuery('state', '');
        $expectedState = (string)$this->request->getSession()->read('Cybozu.oauth_state', '');
        $this->request->getSession()->delete('Cybozu.oauth_state');

        $returnUrl = $this->request->getSession()->read('Cybozu.return_url');
        $this->request->getSession()->delete('Cybozu.return_url');

        if ($receivedState === '' || $receivedState !== $expectedState) {
            $this->Flash->error(__('Invalid OAuth state. Please try again.'));
            $this->redirect($returnUrl ?? ['controller' => 'Pages', 'action' => 'display', 'home']);

            return;
        }

        $error = $this->request->getQuery('error');
        if ($error) {
            $this->Flash->error(__('Cybozu authorization was denied: {0}', $error));
            $this->redirect($returnUrl ?? ['controller' => 'Pages', 'action' => 'display', 'home']);

            return;
        }

        $code = (string)$this->request->getQuery('code', '');
        if ($code === '') {
            $this->Flash->error(__('Authorization code not found.'));
            $this->redirect($returnUrl ?? ['controller' => 'Pages', 'action' => 'display', 'home']);

            return;
        }

        /** @var \App\Model\Entity\User $currentUser */
        $identityId  = (string)$this->Authentication->getIdentity()->getIdentifier();
        /** @var \App\Model\Table\UsersTable $Users */
        $Users       = $this->fetchTable('Users');
        $currentUser = $Users->get($identityId);

        try {
            // 2) token 取得
            $tokenData = $cybozuService->fetchToken($code);

            // 3) whoami アプリでレコード経由の本人確認
            // callback 時点ではまだ saveToken していないため access_token を直接渡してクライアントを生成
            $client    = new KintoneApiClient(
                $cybozuService->getSubdomain(),
                $tokenData['access_token'],
            );
            $loginCode = $whoAmIService->resolveLoginCode($client, (string)$currentUser->username);
        } catch (RuntimeException $e) {
            $this->Flash->error(__('Cybozu connection failed: {0}', $e->getMessage()));
            $this->redirect($returnUrl ?? ['controller' => 'Users', 'action' => 'view', $currentUser->id]);

            return;
        }

        // 4) 照合
        if ((string)$currentUser->kintone_username !== $loginCode) {
            $this->Flash->error(__(
                'Kintone login code mismatch. Expected "{0}", but got "{1}". Check the kintone_username setting.',
                $currentUser->kintone_username,
                $loginCode
            ));
            $this->redirect($returnUrl ?? ['controller' => 'Users', 'action' => 'view', $currentUser->id]);

            return;
        }

        // 5) トークン保存 + 連携フラグ更新
        try {
            $cybozuService->saveToken((string)$currentUser->id, $tokenData);

            $user = $Users->patchEntity($currentUser, ['is_kintone_linked' => true]);

            if (!$Users->save($user)) {
                throw new RuntimeException('Failed to update is_kintone_linked.');
            }
        } catch (RuntimeException $e) {
            $this->Flash->error(__('Cybozu connection failed: {0}', $e->getMessage()));
            $this->redirect($returnUrl ?? ['controller' => 'Users', 'action' => 'view', $currentUser->id]);

            return;
        }

        $this->Flash->success(__('Cybozu account linked successfully! ({0})', $loginCode));
        $this->redirect($returnUrl ?? ['controller' => 'Users', 'action' => 'view', $currentUser->id]);
    }

    /**
     * 連携解除: cybozu_auths を削除し is_kintone_linked を false に戻す
     *
     * POST /auth/cybozu/revoke
     */
    public function revoke(CybozuOAuthService $cybozuService): void
    {
        $this->request->allowMethod(['post']);

        $currentUser = $this->Authentication->getIdentity()->getOriginalData();
        $returnUrl   = $this->referer(['controller' => 'Pages', 'action' => 'display', 'home'], true);

        try {
            $cybozuService->revokeToken((string)$currentUser->id);

            /** @var \App\Model\Table\UsersTable $Users */
            $Users = $this->fetchTable('Users');
            $user  = $Users->get($currentUser->id);
            $user  = $Users->patchEntity($user, ['is_kintone_linked' => false]);
            $Users->save($user);
        } catch (RuntimeException $e) {
            $this->Flash->error(__('Failed to revoke Cybozu link: {0}', $e->getMessage()));
            $this->redirect($returnUrl);

            return;
        }

        $this->Flash->success(__('Cybozu link has been revoked.'));
        $this->redirect($returnUrl);
    }
}