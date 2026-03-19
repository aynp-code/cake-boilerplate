<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateKintoneWebhookQueues extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('kintone_webhook_queues', [
            'id' => false,
            'primary_key' => ['id'],
        ]);
        $table
            ->addColumn('id', 'uuid')
            ->addColumn('app_id', 'integer', ['null' => false, 'comment' => 'kintone アプリID'])
            ->addColumn('record_id', 'integer', ['null' => false, 'comment' => 'kintone レコードID ($id)'])
            ->addColumn('event_type', 'string', ['limit' => 100, 'null' => false, 'comment' => 'webhookイベント種別 (APP.RECORD.CREATE 等)'])
            ->addColumn('payload', 'text', ['null' => false, 'comment' => 'webhookペイロード JSON'])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'pending',
                'comment' => 'pending / processing / done / failed',
            ])
            ->addColumn('attempts', 'integer', ['null' => false, 'default' => 0, 'comment' => '処理試行回数'])
            ->addColumn('error_message', 'text', ['null' => true, 'default' => null, 'comment' => '最後のエラーメッセージ'])
            ->addColumn('scheduled_at', 'datetime', ['null' => true, 'default' => null, 'comment' => 'リトライ予定日時 (null = 即時実行可)'])
            ->addColumn('processed_at', 'datetime', ['null' => true, 'default' => null, 'comment' => '処理完了日時'])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])

            ->addIndex(['status', 'scheduled_at'], ['name' => 'IDX_KWQUEUE_STATUS_SCHEDULED'])
            ->addIndex(['app_id'], ['name' => 'IDX_KWQUEUE_APP_ID'])
            ->create();
    }
}
