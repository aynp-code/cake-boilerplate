<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * kintone webhook 同期レコードテーブルを作成する。
 *
 * dereuromark/cakephp-queue プラグインがキュー管理を担うため、
 * このマイグレーションでは kintone から同期したデータの保存先のみを作成する。
 */
class CreateKintoneWebhookRecords extends BaseMigration
{
    public function up(): void
    {
        $this->table('kintone_webhook_records', [
                'id' => false,
                'primary_key' => ['id'],
                'collation' => 'utf8mb4_unicode_ci',
            ])
            ->addColumn('id', 'char', [
                'limit' => 36,
                'null' => false,
                'comment' => 'UUID',
            ])
            ->addColumn('kintone_record_number', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'kintone レコード番号（upsert キー）',
            ])
            ->addColumn('single_line_text', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('multi_line_text', 'text', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('checkbox_value', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON 配列',
            ])
            ->addColumn('dropdown_value', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('radio_button_value', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('attachments', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON 配列（fileKey / name / size / contentType）',
            ])
            ->addColumn('kintone_created_at', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('kintone_creator', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('kintone_updated_at', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('kintone_modifier', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['kintone_record_number'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('kintone_webhook_records')->drop()->save();
    }
}
