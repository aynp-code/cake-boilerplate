<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlterKintoneWebhookQueuesPayloadToLongtext extends BaseMigration
{
    public function up(): void
    {
        $this->table('kintone_webhook_queues')
            ->changeColumn('payload', 'text', [
                'null' => false,
                'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG,
                'comment' => 'webhookペイロード JSON (LONGTEXT)',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('kintone_webhook_queues')
            ->changeColumn('payload', 'text', [
                'null' => false,
                'comment' => 'webhookペイロード JSON',
            ])
            ->update();
    }
}
