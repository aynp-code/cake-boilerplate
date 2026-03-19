<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * KintoneWebhookQueue Entity
 *
 * kintone webhook を受信してキューに積んだ処理待ちジョブ。
 * status: pending → processing → done | failed
 *
 * @property string $id
 * @property int $app_id          kintone アプリID
 * @property int $record_id       kintone レコードID ($id の値)
 * @property string $event_type   APP.RECORD.CREATE / APP.RECORD.UPDATE / APP.RECORD.DELETE 等
 * @property string $payload      webhook ペイロード JSON
 * @property string $status       pending / processing / done / failed
 * @property int $attempts        処理試行回数
 * @property string|null $error_message
 * @property \Cake\I18n\DateTime|null $scheduled_at  リトライ予定日時 (null=即時実行可)
 * @property \Cake\I18n\DateTime|null $processed_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class KintoneWebhookQueue extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'app_id' => true,
        'record_id' => true,
        'event_type' => true,
        'payload' => true,
        'status' => true,
        'attempts' => true,
        'error_message' => true,
        'scheduled_at' => true,
        'processed_at' => true,
        'created' => true,
        'modified' => true,
    ];
}
