<?php
declare(strict_types=1);

namespace App\Controller;

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
 * - CybozuOAuthService をアクション引数で受け取り makeKintoneClient() でクライアントを生成する
 * - SampleKintoneService はトークンを知らない（クライアントを受け取るだけ）
 * - kintone 未連携・通信エラーは Flash エラーで通知し index へリダイレクト
 *
 * ## 新しい kintone アプリのコントローラを作る場合
 *
 * このファイルをコピーして以下を変更してください。
 *   1. クラス名
 *   2. use している AppService のクラス名
 *   3. 各アクション内の new SampleKintoneService() の部分
 *   4. normalizePostData() のフィールド定義
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
            $client  = $this->makeClient($cybozuService);
            $records = $service->findAll($client, 'order by $id desc');
        } catch (RuntimeException $e) {
            $this->Flash->error($e->getMessage());
            $records = [];
        }

        $serviceTypes = SampleKintoneService::SERVICE_TYPES;

        $this->set(compact('records', 'serviceTypes'));
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
            $client = $this->makeClient($cybozuService);
            $record = $service->find($client, $id);
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
        $serviceTypes = SampleKintoneService::SERVICE_TYPES;

        if ($this->request->is('post')) {
            $service = new SampleKintoneService();

            try {
                $client   = $this->makeClient($cybozuService);
                $recordId = $service->create($client, $this->normalizePostData());
                $this->Flash->success(__('登録しました。（kintone レコード ID: {0}）', $recordId));
                $this->redirect(['action' => 'index']);

                return;
            } catch (RuntimeException $e) {
                $this->Flash->error(__('登録に失敗しました: {0}', $e->getMessage()));
            }
        }

        $this->set(compact('serviceTypes'));
    }

    /**
     * 編集
     *
     * GET  /sample-kintone/edit/{id}  → フォーム表示（既存値をプリフィル）
     * POST /sample-kintone/edit/{id}  → 更新実行
     */
    public function edit(CybozuOAuthService $cybozuService, int $id): void
    {
        $service      = new SampleKintoneService();
        $serviceTypes = SampleKintoneService::SERVICE_TYPES;
        $client       = null;

        try {
            $client = $this->makeClient($cybozuService);
            $record = $service->find($client, $id);
        } catch (RuntimeException $e) {
            $this->Flash->error($e->getMessage());
            $this->redirect(['action' => 'index']);

            return;
        }

        if ($this->request->is(['post', 'put', 'patch'])) {
            try {
                $service->update($client, $id, $this->normalizePostData());
                $this->Flash->success(__('更新しました。'));
                $this->redirect(['action' => 'index']);

                return;
            } catch (RuntimeException $e) {
                $this->Flash->error(__('更新に失敗しました: {0}', $e->getMessage()));
            }
        }

        $this->set(compact('record', 'serviceTypes'));
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
            $client = $this->makeClient($cybozuService);
            $service->delete($client, $id);
            $this->Flash->success(__('削除しました。'));
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
     * 未連携の場合は connect ページへの誘導メッセージを添えて例外を投げる。
     *
     * @throws RuntimeException
     */
    private function makeClient(CybozuOAuthService $cybozuService): KintoneApiClientInterface
    {
        $userId = (string)$this->Authentication->getIdentity()->getIdentifier();

        try {
            return $cybozuService->makeKintoneClient($userId);
        } catch (RuntimeException) {
            throw new RuntimeException(
                'kintone と連携されていません。' .
                '<a href="/auth/cybozu/connect">こちら</a>から連携してください。'
            );
        }
    }

    /**
     * POST データをサービスに渡せる形に正規化する。
     *
     * @return array<string, mixed>
     */
    private function normalizePostData(): array
    {
        $data = $this->request->getData();

        return [
            'service_type' => (string)($data['service_type'] ?? 'kintone'),
            'model_number' => (string)($data['model_number'] ?? ''),
            'product_name' => (string)($data['product_name'] ?? ''),
            'price'        => isset($data['price']) && $data['price'] !== ''
                ? (int)$data['price']
                : null,
            'notes'        => (string)($data['notes'] ?? ''),
        ];
    }
}
