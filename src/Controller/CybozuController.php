<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\CybozuOAuthService;
use Cake\Event\EventInterface;
use RuntimeException;

/**
 * Cybozu OAuth 連携コントローラー
 *
 * /auth/cybozu/connect   → 有効なトークンがあればスキップ、なければ認可URLへリダイレクト
 * /auth/cybozu/callback  → code受け取り → token取得 → ログイン名照合 → DB保存
 * /auth/cybozu/revoke    → 連携解除
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
    public function connect(): void
    {
        $this->request->allowMethod(['get']);

        try {
            $service = new CybozuOAuthService();
        } catch (RuntimeException $e) {
            $this->Flash->error(__('Cybozu is not configured. Contact the administrator.'));
            $this->redirect(['controller' => 'Pages', 'action' => 'display', 'home']);
            return;
        }

        $currentUserId = (string)$this->Authentication->getIdentity()->getIdentifier();

        // 有効なトークンが既にある場合はループを防ぐためスキップ
        $token = $service->getValidToken($currentUserId);
        if ($token !== null) {
            $this->Flash->success(__('Cybozu account is already linked.'));
            $this->redirect($this->referer(['controller' => 'Users', 'action' => 'view', $currentUserId]));
            return;
        }

        // 連携完了後に戻るURLを保存（OAuth外部サイト経由になるためセッションに保存）
        $returnUrl = $this->referer(['controller' => 'Pages', 'action' => 'display', 'home'], true);
        $this->request->getSession()->write('Cybozu.return_url', $returnUrl);

        // CSRF 対策用 state をセッションに保存
        $state = bin2hex(random_bytes(16));
        $this->request->getSession()->write('Cybozu.oauth_state', $state);

        $this->redirect($service->buildAuthorizationUrl($state));
    }

    /**
     * Step 2: cybozu からのコールバック
     *
     * GET /auth/cybozu/callback?code=xxx&state=yyy
     *
     * 処理の流れ：
     *  1. state 検証（CSRF 対策）
     *  2. code → access_token / refresh_token 取得
     *  3. レコード追加 → $creator.code 取得 → レコード削除
     *  4. creator.code と users.kintone_username を照合
     *  5. 一致 → cybozu_auths に保存 + is_kintone_linked = true
     */
    public function callback(): void
    {
        $this->request->allowMethod(['get']);

        // ---- state 検証 ----
        $receivedState = (string)$this->request->getQuery('state', '');
        $expectedState = (string)$this->request->getSession()->read('Cybozu.oauth_state', '');
        $this->request->getSession()->delete('Cybozu.oauth_state');

        // 元のページへ戻るURL（取得後は削除）
        $returnUrl = $this->request->getSession()->read('Cybozu.return_url');
        $this->request->getSession()->delete('Cybozu.return_url');

        if ($receivedState === '' || $receivedState !== $expectedState) {
            $this->Flash->error(__('Invalid OAuth state. Please try again.'));
            $this->redirect($returnUrl ?? ['controller' => 'Pages', 'action' => 'display', 'home']);
            return;
        }

        // ---- error パラメータ確認 ----
        $error = $this->request->getQuery('error');
        if ($error) {
            $this->Flash->error(__('Cybozu authorization was denied: {0}', $error));
            $this->redirect($returnUrl ?? ['controller' => 'Pages', 'action' => 'display', 'home']);
            return;
        }

        // ---- code 取得 ----
        $code = (string)$this->request->getQuery('code', '');
        if ($code === '') {
            $this->Flash->error(__('Authorization code not found.'));
            $this->redirect($returnUrl ?? ['controller' => 'Pages', 'action' => 'display', 'home']);
            return;
        }

        /** @var \App\Model\Entity\User $currentUser */
        $identityId = (string)$this->Authentication->getIdentity()->getIdentifier();
        /** @var \App\Model\Table\UsersTable $Users */
        $Users = $this->fetchTable('Users');
        $currentUser = $Users->get($identityId);

        try {
            $service = new CybozuOAuthService();

            // 2) token 取得
            $tokenData = $service->fetchToken($code);

            // 3) レコード追加 → creator.code 取得 → レコード削除
            $loginCode = $service->resolveLoginCode($tokenData['access_token'], (string)$currentUser->username);

        } catch (RuntimeException $e) {
            $this->Flash->error(__('Cybozu connection failed: {0}', $e->getMessage()));
            $this->redirect($returnUrl ?? ['controller' => 'Users', 'action' => 'view', $currentUser->id]);
            return;
        }

        // ---- 4) 照合 ----
        if ((string)$currentUser->kintone_username !== $loginCode) {
            $this->Flash->error(__(
                'Kintone login code mismatch. Expected "{0}", but got "{1}". Check the kintone_username setting.',
                $currentUser->kintone_username,
                $loginCode
            ));
            $this->redirect($returnUrl ?? ['controller' => 'Users', 'action' => 'view', $currentUser->id]);
            return;
        }

        // ---- 5) トークン保存 + 連携フラグ更新 ----
        try {
            // cybozu_auths に upsert
            $service->saveToken((string)$currentUser->id, $tokenData);

            // users.is_kintone_linked = true
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
    public function revoke(): void
    {
        $this->request->allowMethod(['post']);

        $currentUser = $this->Authentication->getIdentity()->getOriginalData();
        $returnUrl   = $this->referer(['controller' => 'Pages', 'action' => 'display', 'home'], true);

        try {
            $service = new CybozuOAuthService();
            $service->revokeToken((string)$currentUser->id);

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