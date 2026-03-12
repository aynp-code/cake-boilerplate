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
 *  | 商品URL       | リンク           | value は {url, label} オブジェクト  |
 *  | 型番          | 文字列（1行）    | 必須、重複禁止、最大64文字          |
 *  | 商品名        | 文字列（1行）    |                                     |
 *  | 価格          | 数値             | 必須、¥前付き                       |
 *  | 特記事項      | 文字列（複数行） |                                     |
 *  | 添付ファイル  | 添付ファイル     | 取得・表示のみ（アップロード未対応）|
 *
 * ## 別のアプリへの応用
 *
 * このクラスをコピーして appId() とフィールドマッピングを書き換えるだけで
 * 新しいアプリのサービスが完成します。
 * AbstractKintoneAppService / KintoneApiClient / CybozuOAuthService は一切触りません。
 */
class SampleKintoneService extends AbstractKintoneAppService
{
    /** 承認ドロップダウンの選択肢（kintone 側の設定と一致させること） */
    public const APPROVAL_OPTIONS = ['未承認', '承認'];

    /** 種別ラジオボタンの選択肢（kintone 側の設定と一致させること） */
    public const CATEGORY_OPTIONS = ['什器', 'ソフトウェア', 'その他'];

    /** タグチェックボックスの選択肢（kintone 側の設定と一致させること） */
    public const TAG_OPTIONS = ['デスク', 'チェア', 'PC関連', '多機能', '人間工学', 'デザイン'];

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
        // チェックボックスは value が文字列の配列で返ってくる
        $tags = $kintoneRecord['タグ']['value'] ?? [];
        if (!is_array($tags)) {
            $tags = [];
        }

        // リンクフィールドの value は文字列で返ってくる
        $urlValue   = $kintoneRecord['商品URL']['value'] ?? '';
        $productUrl = is_string($urlValue) ? $urlValue : '';

        // 添付ファイルは value が [{fileKey, name, contentType, size}, ...] の配列
        $attachments = $kintoneRecord['添付ファイル']['value'] ?? [];
        if (!is_array($attachments)) {
            $attachments = [];
        }

        return [
            'id'           => (int)$this->value($kintoneRecord, '$id', 0),
            'revision'     => (int)$this->value($kintoneRecord, '$revision', 0),
            'approval'     => (string)$this->value($kintoneRecord, '承認', ''),
            'category'     => (string)$this->value($kintoneRecord, '種別', '什器'),
            'tags'         => $tags,
            'release_date' => (string)$this->value($kintoneRecord, '発売日', ''),
            'product_url'  => $productUrl,
            'model_number' => (string)$this->value($kintoneRecord, '型番', ''),
            'product_name' => (string)$this->value($kintoneRecord, '商品名', ''),
            'price'        => $this->value($kintoneRecord, '価格') !== null
                ? (int)$this->value($kintoneRecord, '価格')
                : null,
            'notes'        => (string)$this->value($kintoneRecord, '特記事項', ''),
            'attachments'  => $attachments,
        ];
    }

    /**
     * アプリ内配列 → kintone フィールド形式
     *
     * 渡されたキーのみ送信するため、部分更新（edit）にも対応しています。
     * 添付ファイルはマルチパート送信が必要なため送信対象外とします。
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
            // チェックボックスは文字列の配列で渡す
            $fields['タグ'] = ['value' => (array)$data['tags']];
        }

        if (array_key_exists('release_date', $data)) {
            // 日付は YYYY-MM-DD 形式、空の場合は null
            $fields['発売日'] = ['value' => $data['release_date'] !== '' ? $data['release_date'] : null];
        }

        if (array_key_exists('product_url', $data)) {
            // リンクフィールドの value は文字列で渡す
            $fields['商品URL'] = ['value' => (string)($data['product_url'] ?? '')];
        }

        if (array_key_exists('model_number', $data)) {
            $fields['型番'] = ['value' => $data['model_number']];
        }

        if (array_key_exists('product_name', $data)) {
            $fields['商品名'] = ['value' => $data['product_name']];
        }

        if (array_key_exists('price', $data)) {
            // 数値フィールドは文字列で渡す（null は空文字）
            $fields['価格'] = ['value' => $data['price'] !== null ? (string)$data['price'] : ''];
        }

        if (array_key_exists('notes', $data)) {
            $fields['特記事項'] = ['value' => $data['notes']];
        }

        // 添付ファイルはマルチパート送信が必要なため送信しない

        return $fields;
    }
}