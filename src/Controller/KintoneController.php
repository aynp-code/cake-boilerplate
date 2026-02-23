<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\KintoneOAuthService;
use Cake\Event\EventInterface;
use RuntimeException;

/**
 * Kintone OAuth 連携コントローラー
 *
 * /auth/cybozu/connect   → 有効なトークンがあればスキップ、なければ認可URLへリダイレクト
 * /auth/cybozu/callback  → code受け取り → token取得 → ログイン名照合 → DB保存
 * /auth/cybozu/revoke    → 連携解除
 */
class KintoneController extends AppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
    }

    /**
     * Step 1: 有効なトークンが既にあればスキップ、なければ kintone 認可画面へリダイレクト
     *
     * GET /auth/cybozu/connect
     */
    public function connect(): void
    {
        $this->request->allowMethod(['get']);

        try {
            $service = new KintoneOAuthService();
        } catch (RuntimeException $e) {
            $this->Flash->error(__('Kintone is not configured. Contact the administrator.'));
            $this->redirect(['controller' => 'Pages', 'action' => 'display', 'home']);
            return;
        }

        $currentUserId = (string)$this->Authentication->getIdentity()->getIdentifier();

        // 有効なトークンが既にある場合はループを防ぐためスキップ
        $token = $service->getValidToken($currentUserId);
        if ($token !== null) {
            $this->Flash->success(__('Kintone account is already linked.'));
            $this->redirect(['controller' => 'Users', 'action' => 'view', $currentUserId]);
            return;
        }

        // CSRF 対策用 state をセッションに保存
        $state = bin2hex(random_bytes(16));
        $this->request->getSession()->write('Kintone.oauth_state', $state);

        $this->redirect($service->buildAuthorizationUrl($state));
    }

    /**
     * Step 2: kintone からのコールバック
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
        $expectedState = (string)$this->request->getSession()->read('Kintone.oauth_state', '');
        $this->request->getSession()->delete('Kintone.oauth_state');

        if ($receivedState === '' || $receivedState !== $expectedState) {
            $this->Flash->error(__('Invalid OAuth state. Please try again.'));
            $this->redirect(['action' => 'connect']);
            return;
        }

        // ---- error パラメータ確認 ----
        $error = $this->request->getQuery('error');
        if ($error) {
            $this->Flash->error(__('Kintone authorization was denied: {0}', $error));
            $this->redirect(['controller' => 'Pages', 'action' => 'display', 'home']);
            return;
        }

        // ---- code 取得 ----
        $code = (string)$this->request->getQuery('code', '');
        if ($code === '') {
            $this->Flash->error(__('Authorization code not found.'));
            $this->redirect(['action' => 'connect']);
            return;
        }

        /** @var \App\Model\Entity\User $currentUser */
        $currentUser = $this->Authentication->getIdentity()->getOriginalData();

        try {
            $service = new KintoneOAuthService();

            // 2) token 取得
            $tokenData = $service->fetchToken($code);

            // 3) レコード追加 → creator.code 取得 → レコード削除
            $kintoneLoginCode = $service->resolveKintoneLoginCode($tokenData['access_token'], (string)$currentUser->username);

        } catch (RuntimeException $e) {
            $this->Flash->error(__('Kintone connection failed: {0}', $e->getMessage()));
            $this->redirect(['controller' => 'Users', 'action' => 'view', $currentUser->id]);
            return;
        }

        // ---- 4) 照合 ----
        if ((string)$currentUser->kintone_username !== $kintoneLoginCode) {
            $this->Flash->error(__(
                'Kintone login code mismatch. Expected "{0}", but got "{1}". Check the kintone_username setting.',
                $currentUser->kintone_username,
                $kintoneLoginCode
            ));
            $this->redirect(['controller' => 'Users', 'action' => 'view', $currentUser->id]);
            return;
        }

        // ---- 5) トークン保存 + 連携フラグ更新 ----
        try {
            // cybozu_auths に upsert
            $service->saveToken((string)$currentUser->id, $tokenData);

            // users.is_kintone_linked = true
            /** @var \App\Model\Table\UsersTable $Users */
            $Users = $this->fetchTable('Users');
            $user  = $Users->get($currentUser->id);
            $user  = $Users->patchEntity($user, ['is_kintone_linked' => true]);

            if (!$Users->save($user)) {
                throw new RuntimeException('Failed to update is_kintone_linked.');
            }

        } catch (RuntimeException $e) {
            $this->Flash->error(__('Kintone connection failed: {0}', $e->getMessage()));
            $this->redirect(['controller' => 'Users', 'action' => 'view', $currentUser->id]);
            return;
        }

        $this->Flash->success(__('Kintone account linked successfully! ({0})', $kintoneLoginCode));
        $this->redirect(['controller' => 'Users', 'action' => 'view', $currentUser->id]);
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

        try {
            $service = new KintoneOAuthService();
            $service->revokeToken((string)$currentUser->id);

            /** @var \App\Model\Table\UsersTable $Users */
            $Users = $this->fetchTable('Users');
            $user  = $Users->get($currentUser->id);
            $user  = $Users->patchEntity($user, ['is_kintone_linked' => false]);
            $Users->save($user);

        } catch (RuntimeException $e) {
            $this->Flash->error(__('Failed to revoke Kintone link: {0}', $e->getMessage()));
            $this->redirect(['controller' => 'Users', 'action' => 'view', $currentUser->id]);
            return;
        }

        $this->Flash->success(__('Kintone link has been revoked.'));
        $this->redirect(['controller' => 'Users', 'action' => 'view', $currentUser->id]);
    }
}