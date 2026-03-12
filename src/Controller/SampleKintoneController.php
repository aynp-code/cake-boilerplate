<?php
declare(strict_types=1);

namespace App\Controller;

use App\Exception\KintoneNotLinkedException;
use App\Service\CybozuOAuthService;
use App\Service\KintoneApiClientInterface;
use App\Service\Kintone\SampleKintoneService;
use Cake\Event\EventInterface;
use RuntimeException;

/**
 * kintone サンプルアプリ コントローラー
 *
 * ## 設計方針
 *
 * - データ変換（normalize / extract）は SampleKintoneService が担う
 * - このコントローラは HTTP の入出力（リクエスト受付・リダイレクト・Flash）のみを担う
 * - 未連携エラー（KintoneNotLinkedException）は escape => false で Flash 表示し、連携ページへ誘導
 * - 通常の kintone API エラー（RuntimeException）は escape あり で Flash 表示
 *
 * ## 新しい kintone アプリのコントローラを作る場合
 *
 * このファイルをコピーして以下を変更してください。
 *   1. クラス名
 *   2. use している Service のクラス名（SampleKintoneService の部分）
 *   3. 各アクション内の new SampleKintoneService() の部分
 */
class SampleKintoneController extends AppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
    }

    /**
     * 一覧
     *
     * GET /sample-kintone
     */
    public function index(CybozuOAuthService $cybozuService): void
    {
        $service = new SampleKintoneService();

        try {
            $client  = $this->makeKintoneClient($cybozuService);
            $records = $service->findAll($client, 'order by $id desc');
        } catch (KintoneNotLinkedException $e) {
            $this->Flash->error($e->getMessage(), ['escape' => false]);
            $records = [];
        } catch (RuntimeException $e) {
            $this->Flash->error($e->getMessage());
            $records = [];
        }

        $this->set(compact('records'));
    }

    /**
     * 詳細
     *
     * GET /sample-kintone/view/{id}
     */
    public function view(CybozuOAuthService $cybozuService, int $id): void
    {
        $service = new SampleKintoneService();

        try {
            $client = $this->makeKintoneClient($cybozuService);
            $record = $service->find($client, $id);
        } catch (KintoneNotLinkedException $e) {
            $this->Flash->error($e->getMessage(), ['escape' => false]);
            $this->redirect(['action' => 'index']);
            return;
        } catch (RuntimeException $e) {
            $this->Flash->error($e->getMessage());
            $this->redirect(['action' => 'index']);
            return;
        }

        $this->set(compact('record'));
    }

    /**
     * 新規作成
     *
     * GET  /sample-kintone/add  → フォーム表示
     * POST /sample-kintone/add  → 登録実行
     */
    public function add(CybozuOAuthService $cybozuService): void
    {
        $service         = new SampleKintoneService();
        $approvalOptions = SampleKintoneService::APPROVAL_OPTIONS;
        $categoryOptions = SampleKintoneService::CATEGORY_OPTIONS;
        $tagOptions      = SampleKintoneService::TAG_OPTIONS;

        if ($this->request->is('post')) {
            try {
                $client   = $this->makeKintoneClient($cybozuService);
                $recordId = $service->create($client, $service->normalizePostData($this->request->getData()));
                $this->Flash->success(__('登録しました。（kintone レコード ID: {0}）', $recordId));
                $this->redirect(['action' => 'index']);
                return;
            } catch (KintoneNotLinkedException $e) {
                $this->Flash->error($e->getMessage(), ['escape' => false]);
            } catch (RuntimeException $e) {
                $this->Flash->error(__('登録に失敗しました: {0}', $e->getMessage()));
            }
            // 登録失敗時：送信値をフォームに残す
            // edit と同じく withParsedBody で Form->control() に値をセットする
            $this->request = $this->request->withParsedBody($this->request->getData());
        }

        $this->set(compact('approvalOptions', 'categoryOptions', 'tagOptions'));
    }

    /**
     * 編集
     *
     * GET  /sample-kintone/edit/{id}  → フォーム表示（既存値をプリフィル）
     * POST /sample-kintone/edit/{id}  → 更新実行
     */
    public function edit(CybozuOAuthService $cybozuService, int $id): void
    {
        $service         = new SampleKintoneService();
        $approvalOptions = SampleKintoneService::APPROVAL_OPTIONS;
        $categoryOptions = SampleKintoneService::CATEGORY_OPTIONS;
        $tagOptions      = SampleKintoneService::TAG_OPTIONS;

        try {
            $client = $this->makeKintoneClient($cybozuService);
            $record = $service->find($client, $id);
        } catch (KintoneNotLinkedException $e) {
            $this->Flash->error($e->getMessage(), ['escape' => false]);
            $this->redirect(['action' => 'index']);
            return;
        } catch (RuntimeException $e) {
            $this->Flash->error($e->getMessage());
            $this->redirect(['action' => 'index']);
            return;
        }

        if ($this->request->is(['post', 'put', 'patch'])) {
            try {
                $service->update($client, $id, $service->normalizeUpdateData($this->request->getData()));
                $this->Flash->success(__('更新しました。'));
                $this->redirect(['action' => 'index']);
                return;
            } catch (KintoneNotLinkedException $e) {
                $this->Flash->error($e->getMessage(), ['escape' => false]);
                $this->redirect(['action' => 'index']);
                return;
            } catch (RuntimeException $e) {
                $this->Flash->error(__('更新に失敗しました: {0}', $e->getMessage()));
            }
        }

        // Form->control() が既存値を自動選択できるようリクエストボディにセット
        // POST失敗時は送信値を、GETアクセス時はkintone取得値を使う
        if (!$this->request->is(['post', 'put', 'patch'])) {
            $this->request = $this->request->withParsedBody($record);
        }

        $this->set(compact('record', 'approvalOptions', 'categoryOptions', 'tagOptions'));
    }

    /**
     * 削除
     *
     * POST /sample-kintone/delete/{id}
     */
    public function delete(CybozuOAuthService $cybozuService, int $id): void
    {
        $this->request->allowMethod(['post', 'delete']);

        $service = new SampleKintoneService();

        try {
            $client = $this->makeKintoneClient($cybozuService);
            $service->delete($client, $id);
            $this->Flash->success(__('削除しました。'));
        } catch (KintoneNotLinkedException $e) {
            $this->Flash->error($e->getMessage(), ['escape' => false]);
        } catch (RuntimeException $e) {
            $this->Flash->error(__('削除に失敗しました: {0}', $e->getMessage()));
        }

        $this->redirect(['action' => 'index']);
    }

    // =========================================================================
    // private
    // =========================================================================

    /**
     * kintone クライアントを生成する。
     *
     * 未連携の場合は KintoneNotLinkedException をスローする。
     * この例外はコントローラ側で escape => false で Flash 表示する。
     *
     * @throws KintoneNotLinkedException  未連携の場合
     * @throws RuntimeException           その他のエラー
     */
    private function makeKintoneClient(CybozuOAuthService $cybozuService): KintoneApiClientInterface
    {
        $userId = (string)$this->Authentication->getIdentity()->getIdentifier();

        try {
            return $cybozuService->makeKintoneClient($userId);
        } catch (RuntimeException) {
            throw new KintoneNotLinkedException(
                'kintone と連携されていません。' .
                '<a href="/auth/cybozu/connect">こちら</a>から連携してください。'
            );
        }
    }
}
