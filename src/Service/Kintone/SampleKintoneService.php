<?php
declare(strict_types=1);

namespace App\Service\Kintone;

/**
 * kintone サンプルアプリ（app id: 11）サービス
 *
 * ## フィールド定義
 *
 *  | フィールドコード | 種別             | 備考                                |
 *  |--------------|------------------|-------------------------------------|
 *  | 承認          | ドロップダウン    | 未承認 / 承認、必須                 |
 *  | 種別          | ラジオボタン      | 什器 / ソフトウェア / その他        |
 *  | タグ          | チェックボックス  | 複数選択、array で保持              |
 *  | 発売日        | 日付             | YYYY-MM-DD 形式、必須               |
 *  | 商品URL       | リンク           | value は文字列                      |
 *  | 型番          | 文字列（1行）    | 必須、重複禁止、最大64文字          |
 *  | 商品名        | 文字列（1行）    |                                     |
 *  | 価格          | 数値             | 必須、¥前付き                       |
 *  | 特記事項      | 文字列（複数行） |                                     |
 *  | 添付ファイル  | 添付ファイル     | アップロード・削除対応              |
 */
class SampleKintoneService extends AbstractKintoneAppService
{
    public const APPROVAL_OPTIONS = ['未承認', '承認'];
    public const CATEGORY_OPTIONS = ['什器', 'ソフトウェア', 'その他'];
    public const TAG_OPTIONS = ['デスク', 'チェア', 'PC関連', '多機能', '人間工学', 'デザイン'];

    /**
     * Return the kintone app ID.
     *
     * @return int
     */
    protected function appId(): int
    {
        return 11;
    }

    /**
     * kintone レコード → アプリ内配列
     *
     * @param array<string, mixed> $kintoneRecord
     * @return array<string, mixed>
     */
    protected function toRecord(array $kintoneRecord): array
    {
        $tags = $kintoneRecord['タグ']['value'] ?? [];
        if (!is_array($tags)) {
            $tags = [];
        }

        // リンクフィールドの value は文字列
        $urlValue = $kintoneRecord['商品URL']['value'] ?? '';
        $productUrl = is_string($urlValue) ? $urlValue : '';

        // 添付ファイルは [{fileKey, name, contentType, size}, ...] の配列
        $attachments = $kintoneRecord['添付ファイル']['value'] ?? [];
        if (!is_array($attachments)) {
            $attachments = [];
        }

        return [
            'id' => (int)$this->value($kintoneRecord, '$id', 0),
            'revision' => (int)$this->value($kintoneRecord, '$revision', 0),
            'approval' => (string)$this->value($kintoneRecord, '承認', ''),
            'category' => (string)$this->value($kintoneRecord, '種別', '什器'),
            'tags' => $tags,
            'release_date' => (string)$this->value($kintoneRecord, '発売日', ''),
            'product_url' => $productUrl,
            'model_number' => (string)$this->value($kintoneRecord, '型番', ''),
            'product_name' => (string)$this->value($kintoneRecord, '商品名', ''),
            'price' => $this->value($kintoneRecord, '価格') !== null
                ? (int)$this->value($kintoneRecord, '価格')
                : null,
            'notes' => (string)$this->value($kintoneRecord, '特記事項', ''),
            'attachments' => $attachments,
        ];
    }

    /**
     * アプリ内配列 → kintone フィールド形式
     *
     * 添付ファイルは $data['attachments'] に fileKey の配列を渡すと送信される。
     * キーが存在しない場合は送信しない（部分更新対応）。
     *
     * @param array<string, mixed> $data
     * @return array<string, array{value: mixed}>
     */
    protected function toKintoneFields(array $data): array
    {
        $fields = [];

        if (array_key_exists('approval', $data)) {
            $fields['承認'] = ['value' => $data['approval']];
        }

        if (array_key_exists('category', $data)) {
            $fields['種別'] = ['value' => $data['category']];
        }

        if (array_key_exists('tags', $data)) {
            $fields['タグ'] = ['value' => (array)$data['tags']];
        }

        if (array_key_exists('release_date', $data)) {
            $fields['発売日'] = ['value' => $data['release_date'] !== '' ? $data['release_date'] : null];
        }

        if (array_key_exists('product_url', $data)) {
            $fields['商品URL'] = ['value' => (string)($data['product_url'] ?? '')];
        }

        if (array_key_exists('model_number', $data)) {
            $fields['型番'] = ['value' => $data['model_number']];
        }

        if (array_key_exists('product_name', $data)) {
            $fields['商品名'] = ['value' => $data['product_name']];
        }

        if (array_key_exists('price', $data)) {
            $fields['価格'] = ['value' => $data['price'] !== null ? (string)$data['price'] : ''];
        }

        if (array_key_exists('notes', $data)) {
            $fields['特記事項'] = ['value' => $data['notes']];
        }

        if (array_key_exists('attachments', $data)) {
            // kintone の添付ファイルは [{fileKey: '...'}] の配列形式で渡す
            // 既存ファイルの保持と新規アップロードの両方を含む
            $fields['添付ファイル'] = [
                'value' => array_map(
                    fn(string $key) => ['fileKey' => $key],
                    (array)$data['attachments'],
                ),
            ];
        }

        return $fields;
    }

    /**
     * 新規作成用 POST データ正規化（全フィールド）
     *
     * 添付ファイルの fileKey は別途 uploadFiles() で取得してから渡すこと。
     *
     * @param array<string, mixed> $requestData  $this->request->getData() の値
     * @param array<int, string>   $fileKeys     アップロード済みファイルの fileKey 配列
     * @return array<string, mixed>
     */
    public function normalizePostData(array $requestData, array $fileKeys = []): array
    {
        return [
            'approval' => (string)($requestData['approval'] ?? ''),
            'category' => $this->extractRadio($requestData, 'category', '什器'),
            'tags' => $this->extractCheckbox($requestData, 'tags'),
            'release_date' => (string)($requestData['release_date'] ?? ''),
            'product_url' => (string)($requestData['product_url'] ?? ''),
            'model_number' => (string)($requestData['model_number'] ?? ''),
            'product_name' => (string)($requestData['product_name'] ?? ''),
            'price' => isset($requestData['price']) && $requestData['price'] !== ''
                ? (int)$requestData['price']
                : null,
            'notes' => (string)($requestData['notes'] ?? ''),
            'attachments' => $fileKeys,
        ];
    }

    /**
     * 更新用 POST データ正規化（型番を除外）
     *
     * 添付ファイルの扱い：
     *   - $requestData['existing_file_keys'][]  → 削除されなかった既存ファイルの fileKey
     *   - $fileKeys（引数）                     → 今回新規アップロードした fileKey
     *   → 両者をマージして kintone に送信することで「残す既存 + 新規追加」が実現する
     *
     * @param array<string, mixed> $requestData  $this->request->getData() の値
     * @param array<int, string>   $fileKeys     アップロード済みファイルの fileKey 配列
     * @return array<string, mixed>
     */
    public function normalizeUpdateData(array $requestData, array $fileKeys = []): array
    {
        // フォームの hidden で送られてきた「残す既存ファイル」の fileKey
        $existingKeys = (array)($requestData['existing_file_keys'] ?? []);
        $existingKeys = array_values(array_filter(array_map('strval', $existingKeys)));

        return [
            'approval' => (string)($requestData['approval'] ?? ''),
            'category' => $this->extractRadio($requestData, 'category', '什器'),
            'tags' => $this->extractCheckbox($requestData, 'tags'),
            'release_date' => (string)($requestData['release_date'] ?? ''),
            'product_url' => (string)($requestData['product_url'] ?? ''),
            'product_name' => (string)($requestData['product_name'] ?? ''),
            'price' => isset($requestData['price']) && $requestData['price'] !== ''
                ? (int)$requestData['price']
                : null,
            'notes' => (string)($requestData['notes'] ?? ''),
            // 残す既存ファイル + 今回新規アップロード
            'attachments' => array_merge($existingKeys, $fileKeys),
        ];
    }
}
