<?php
declare(strict_types=1);

namespace App\Service\Kintone;

/**
 * kintone サンプルアプリ（app id: 11）サービス
 *
 * ## フィールド定義
 *
 *  | フィールドコード | 種別           | 備考                                                                                    |
 *  |--------------|----------------|-----------------------------------------------------------------------------------------|
 *  | サービス種別   | ラジオボタン    | kintone / サイボウズ Office / Garoon / セキュアアクセス / ディスク増設 / V-Cube / メールサーバー |
 *  | 型番          | 文字列（1行）   | 値の重複禁止                                                                              |
 *  | 商品名        | 文字列（1行）   |                                                                                         |
 *  | 価格          | 数値           |                                                                                         |
 *  | 特記事項      | 文字列（複数行） |                                                                                         |
 *
 * ## 別のアプリへの応用
 *
 * このクラスをコピーして appId() とフィールドマッピングを書き換えるだけで
 * 新しいアプリのサービスが完成します。
 * AbstractKintoneAppService / KintoneApiClient / CybozuOAuthService は一切触りません。
 */
class SampleKintoneService extends AbstractKintoneAppService
{
    /** サービス種別の選択肢（ラジオボタンの選択肢と一致させること） */
    public const SERVICE_TYPES = [
        'kintone',
        'サイボウズ Office',
        'Garoon',
        'セキュアアクセス',
        'ディスク増設',
        'V-Cube',
        'メールサーバー',
    ];

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
        return [
            'id'           => (int)$this->value($kintoneRecord, '$id', 0),
            'revision'     => (int)$this->value($kintoneRecord, '$revision', 0),
            'service_type' => (string)$this->value($kintoneRecord, 'サービス種別', 'kintone'),
            'model_number' => (string)$this->value($kintoneRecord, '型番', ''),
            'product_name' => (string)$this->value($kintoneRecord, '商品名', ''),
            'price'        => $this->value($kintoneRecord, '価格') !== null
                ? (int)$this->value($kintoneRecord, '価格')
                : null,
            'notes'        => (string)$this->value($kintoneRecord, '特記事項', ''),
        ];
    }

    /**
     * アプリ内配列 → kintone フィールド形式
     *
     * 渡されたキーのみ更新するため、部分更新（edit）にも対応しています。
     *
     * @param array<string, mixed> $data
     * @return array<string, array{value: mixed}>
     */
    protected function toKintoneFields(array $data): array
    {
        $fields = [];

        if (array_key_exists('service_type', $data)) {
            $fields['サービス種別'] = ['value' => $data['service_type']];
        }

        if (array_key_exists('model_number', $data)) {
            $fields['型番'] = ['value' => $data['model_number']];
        }

        if (array_key_exists('product_name', $data)) {
            $fields['商品名'] = ['value' => $data['product_name']];
        }

        if (array_key_exists('price', $data)) {
            // kintone の数値フィールドは文字列で渡す（null は空文字）
            $fields['価格'] = ['value' => $data['price'] !== null ? (string)$data['price'] : ''];
        }

        if (array_key_exists('notes', $data)) {
            $fields['特記事項'] = ['value' => $data['notes']];
        }

        return $fields;
    }
}
