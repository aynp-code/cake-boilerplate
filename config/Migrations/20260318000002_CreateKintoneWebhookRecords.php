<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateKintoneWebhookRecords extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('kintone_webhook_records', [
            'id' => false,
            'primary_key' => ['id'],
        ]);
        $table
            ->addColumn('id', 'uuid')

            // ---- kintone レコード識別子 ----
            ->addColumn('kintone_record_number', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'kintone レコード番号 (RECORD_NUMBER) — upsert キー',
            ])

            // ---- kintone フィールド ----
            ->addColumn('single_line_text', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => '文字列1行 (SINGLE_LINE_TEXT)',
            ])
            ->addColumn('multi_line_text', 'text', [
                'null' => true,
                'default' => null,
                'comment' => '文字列複数行 (MULTI_LINE_TEXT)',
            ])
            ->addColumn('checkbox_value', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'チェックボックス (CHECK_BOX) — JSON配列で保存',
            ])
            ->addColumn('dropdown_value', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'ドロップダウン (DROP_DOWN)',
            ])
            ->addColumn('radio_button_value', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'ラジオボタン (RADIO_BUTTON)',
            ])
            ->addColumn('attachments', 'text', [
                'null' => true,
                'default' => null,
                'comment' => '添付ファイル (FILE) — JSON配列で保存 [{fileKey, name, size, contentType}]',
            ])

            // ---- kintone 組み込みフィールド ----
            ->addColumn('kintone_created_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'kintone 作成日時 (CREATED_TIME)',
            ])
            ->addColumn('kintone_creator', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'kintone 作成者 表示名 (CREATOR)',
            ])
            ->addColumn('kintone_updated_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'kintone 更新日時 (UPDATED_TIME)',
            ])
            ->addColumn('kintone_modifier', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'kintone 更新者 表示名 (MODIFIER)',
            ])

            // ---- 監査カラム ----
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])

            ->addIndex(['kintone_record_number'], ['unique' => true, 'name' => 'UQ_KWR_RECORD_NUMBER'])
            ->create();
    }
}
