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
 * - CybozuOAuthService をアクション引数で受け取り makeKintoneClient() でクライアントを生成する
 * - SampleKintoneService はトークンを知らない（クライアントを受け取るだけ）
 * - 未連携エラー（KintoneNotLinkedException）は escape => false で Flash 表示し、連携ページへ誘導
 * - 通常の kintone API エラー（RuntimeException）は escape あり で Flash 表示
 *
 * ## 新しい kintone アプリのコントローラを作る場合
 *
 * このファイルをコピーして以下を変更してください。
 *   1. クラス名
 *   2. use している Service のクラス名
 *   3. 各アクション内の new SampleKintoneService() の部分
 *   4. normalizePostData() / normalizeUpdateData() のフィールド定義
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
            $client = $this->makeClient($cybozuService);
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
        $approvalOptions = SampleKintoneService::APPROVAL_OPTIONS;
        $categoryOptions = SampleKintoneService::CATEGORY_OPTIONS;
        $tagOptions      = SampleKintoneService::TAG_OPTIONS;

        if ($this->request->is('post')) {
            $service = new SampleKintoneService();

            try {
                $client   = $this->makeClient($cybozuService);
                $recordId = $service->create($client, $this->normalizePostData());
                $this->Flash->success(__('登録しました。（kintone レコード ID: {0}）', $recordId));
                $this->redirect(['action' => 'index']);
                return;
            } catch (KintoneNotLinkedException $e) {
                $this->Flash->error($e->getMessage(), ['escape' => false]);
            } catch (RuntimeException $e) {
                $this->Flash->error(__('登録に失敗しました: {0}', $e->getMessage()));
            }
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
        $client          = null;

        try {
            $client = $this->makeClient($cybozuService);
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
                $service->update($client, $id, $this->normalizeUpdateData());
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

        // GETアクセス時はkintoneから取得した値をフォームにセットする
        // これにより Form->control() が自動的に選択状態を復元できる
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
            $client = $this->makeClient($cybozuService);
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
    private function makeClient(CybozuOAuthService $cybozuService): KintoneApiClientInterface
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

    /**
     * 新規作成用 POST データ正規化（全フィールド）
     *
     * @return array<string, mixed>
     */
    private function normalizePostData(): array
    {
        $data = $this->request->getData();

        return [
            'approval'     => (string)($data['approval'] ?? ''),
            'category'     => $this->extractRadio($data, 'category', '什器'),
            'tags'         => $this->extractCheckbox($data, 'tags'),
            'release_date' => (string)($data['release_date'] ?? ''),
            'product_url'  => (string)($data['product_url'] ?? ''),
            'model_number' => (string)($data['model_number'] ?? ''),
            'product_name' => (string)($data['product_name'] ?? ''),
            'price'        => isset($data['price']) && $data['price'] !== ''
                ? (int)$data['price']
                : null,
            'notes'        => (string)($data['notes'] ?? ''),
        ];
    }

    /**
     * 更新用 POST データ正規化（型番を除外）
     *
     * 型番は「値の重複禁止」フィールドのため、更新時に同じ値を送信すると
     * kintone が重複エラーを返す。登録後は変更不可として更新データから除外する。
     *
     * @return array<string, mixed>
     */
    private function normalizeUpdateData(): array
    {
        $data = $this->request->getData();

        return [
            'approval'     => (string)($data['approval'] ?? ''),
            'category'     => $this->extractRadio($data, 'category', '什器'),
            'tags'         => $this->extractCheckbox($data, 'tags'),
            'release_date' => (string)($data['release_date'] ?? ''),
            'product_url'  => (string)($data['product_url'] ?? ''),
            'product_name' => (string)($data['product_name'] ?? ''),
            'price'        => isset($data['price']) && $data['price'] !== ''
                ? (int)$data['price']
                : null,
            'notes'        => (string)($data['notes'] ?? ''),
        ];
    }

    /**
     * ラジオボタンの値を取り出す。
     *
     * Form->control(type=>'radio') は通常 $data[$name] に文字列で入るが、
     * CakePHP のバージョンや設定によって $data[$name]['_ids'][0] に入る場合もある。
     * どちらでも正しく取り出せるように吸収する。
     *
     * @param array<string, mixed> $data
     */
    private function extractRadio(array $data, string $name, string $default): string
    {
        $value = $data[$name] ?? null;

        // 通常パターン: 文字列で直接入ってくる
        if (is_string($value) && $value !== '') {
            return $value;
        }

        // CakePHP が _ids 形式で送ってきた場合
        if (is_array($value) && isset($value['_ids'][0])) {
            return (string)$value['_ids'][0];
        }

        return $default;
    }

    /**
     * チェックボックスの値を配列で取り出す。
     *
     * Form->control(multiple=>'checkbox') は $data[$name] に配列で入るか、
     * $data[$name]['_ids'] に入る場合がある。どちらでも正しく取り出せるように吸収する。
     *
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function extractCheckbox(array $data, string $name): array
    {
        $value = $data[$name] ?? [];

        // 通常パターン: 配列で直接入ってくる（name="tags[]" の場合）
        if (is_array($value) && !isset($value['_ids'])) {
            return array_values(array_filter(array_map('strval', $value)));
        }

        // CakePHP が _ids 形式で送ってきた場合（multiple=>'checkbox'）
        if (is_array($value) && isset($value['_ids']) && is_array($value['_ids'])) {
            return array_values(array_filter(array_map('strval', $value['_ids'])));
        }

        return [];
    }
}