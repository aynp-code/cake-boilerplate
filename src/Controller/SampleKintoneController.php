<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Trait\KintoneClientTrait;
use App\Exception\KintoneNotLinkedException;
use App\Service\CybozuOAuthService;
use App\Service\Kintone\SampleKintoneService;
use Cake\Event\EventInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * kintone サンプルアプリ コントローラー
 *
 * ## 設計方針
 *
 * - makeClient() は KintoneClientTrait が提供する（全kintoneコントローラで共通）
 * - データ変換（normalize）は SampleKintoneService が担う
 * - このコントローラは HTTP の入出力のみを担う
 * - 未連携エラー（KintoneNotLinkedException）は escape => false で Flash 表示し、連携ページへ誘導
 * - 通常の kintone API エラー（RuntimeException）は escape あり で Flash 表示
 *
 * ## 新しい kintone アプリのコントローラを作る場合
 *
 * このファイルをコピーして以下を変更してください。
 *   1. クラス名
 *   2. use している Service のクラス名
 *   3. 各アクション内の new SampleKintoneService() の部分
 */
class SampleKintoneController extends AppController
{
    use KintoneClientTrait;

    /**
     * Actions to perform before the controller action is run.
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event The event object.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
    }

    /**
     * 一覧
     */
    public function index(CybozuOAuthService $cybozuService): void
    {
        $service = new SampleKintoneService();

        try {
            $client = $this->makeClient($cybozuService);
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
     *
     * 添付ファイルの流れ:
     *   1. アップロードされたファイルを uploadFiles() で kintone に送信 → fileKey 取得
     *   2. normalizePostData() に fileKey を渡してレコードデータを生成
     *   3. create() でレコード登録（添付ファイルも含む）
     */
    public function add(CybozuOAuthService $cybozuService): void
    {
        $service = new SampleKintoneService();
        $approvalOptions = SampleKintoneService::APPROVAL_OPTIONS;
        $categoryOptions = SampleKintoneService::CATEGORY_OPTIONS;
        $tagOptions = SampleKintoneService::TAG_OPTIONS;

        if ($this->request->is('post')) {
            try {
                $client = $this->makeClient($cybozuService);

                // ① 添付ファイルをアップロードして fileKey を取得
                $uploadedFiles = $this->getUploadedFiles('attachments');
                $fileKeys = $service->uploadFiles($client, $uploadedFiles);

                // ② レコード登録（fileKey を含む）
                $recordId = $service->create(
                    $client,
                    $service->normalizePostData($this->request->getData(), $fileKeys),
                );

                $this->Flash->success(__('登録しました。（kintone レコード ID: {0}）', $recordId));
                $this->redirect(['action' => 'index']);

                return;
            } catch (KintoneNotLinkedException $e) {
                $this->Flash->error($e->getMessage(), ['escape' => false]);
            } catch (RuntimeException $e) {
                $this->Flash->error(__('登録に失敗しました: {0}', $e->getMessage()));
            }

            // 登録失敗時：送信値をフォームに残す
            $this->request = $this->request->withParsedBody($this->request->getData());
        }

        $this->set(compact('approvalOptions', 'categoryOptions', 'tagOptions'));
    }

    /**
     * 編集
     *
     * GET  /sample-kintone/edit/{id}  → フォーム表示
     * POST /sample-kintone/edit/{id}  → 更新実行
     *
     * 添付ファイルの流れ:
     *   1. フォームの hidden[existing_file_keys][] で「残す既存ファイルの fileKey」を受け取る
     *      （削除したいファイルは hidden を削除してあるため送信されない）
     *   2. 新規アップロードファイルを uploadFiles() で kintone に送信 → fileKey 取得
     *   3. normalizeUpdateData() で 既存(残す分) + 新規 をマージ
     *   4. update() でレコード更新（添付ファイルフィールドを上書き）
     */
    public function edit(CybozuOAuthService $cybozuService, int $id): void
    {
        $service = new SampleKintoneService();
        $approvalOptions = SampleKintoneService::APPROVAL_OPTIONS;
        $categoryOptions = SampleKintoneService::CATEGORY_OPTIONS;
        $tagOptions = SampleKintoneService::TAG_OPTIONS;

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
                // ① 新規アップロードファイルを kintone に送信
                $uploadedFiles = $this->getUploadedFiles('attachments');
                $fileKeys = $service->uploadFiles($client, $uploadedFiles);

                // ② レコード更新（既存ファイル保持 + 新規追加）
                $service->update(
                    $client,
                    $id,
                    $service->normalizeUpdateData($this->request->getData(), $fileKeys),
                );

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
        if (!$this->request->is(['post', 'put', 'patch'])) {
            $this->request = $this->request->withParsedBody($record);
        }

        $this->set(compact('record', 'approvalOptions', 'categoryOptions', 'tagOptions'));
    }

    /**
     * 削除
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
     * アップロードされたファイルを取得する。
     *
     * $this->request->getUploadedFiles() はネストした配列を返すため、
     * フィールド名を指定して UploadedFileInterface の配列を取り出す。
     *
     * @return array<int, \Psr\Http\Message\UploadedFileInterface>
     */
    private function getUploadedFiles(string $fieldName): array
    {
        $uploaded = $this->request->getUploadedFiles();
        $files = $uploaded[$fieldName] ?? [];

        if (!is_array($files)) {
            return [];
        }

        // 空ファイル（ファイル未選択）を除外
        return array_values(array_filter(
            $files,
            fn($f) => $f instanceof UploadedFileInterface
                && $f->getError() === UPLOAD_ERR_OK,
        ));
    }
}
