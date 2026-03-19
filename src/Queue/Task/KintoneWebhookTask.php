<?php
declare(strict_types=1);

namespace App\Queue\Task;

use App\Service\Kintone\AbstractKintoneWebhookProcessor;
use Cake\Core\Configure;
use Queue\Queue\Task;
use RuntimeException;

/**
 * KintoneWebhook Queue Task
 *
 * kintone webhook イベントをキューから取り出し、対応するプロセッサに委譲する。
 *
 * ## ジョブデータ
 * - app_id    (int)    kintone アプリID
 * - record_id (int)    kintone レコードID
 * - event_type (string) イベント種別 (ADD_RECORD / UPDATE_RECORD / DELETE_RECORD 等)
 *
 * ## プロセッサ登録 (config/app_local.php)
 * ```php
 * 'Cybozu' => [
 *     'webhook' => [
 *         'apps' => [
 *             123 => [
 *                 'api_token' => 'your-token',
 *                 'processor' => \App\Service\Kintone\SampleKintoneWebhookProcessor::class,
 *             ],
 *         ],
 *     ],
 * ],
 * ```
 *
 * ## ワーカー起動
 * ```bash
 * bin/cake queue run
 * ```
 */
class KintoneWebhookTask extends Task
{
    /**
     * タイムアウト秒数。kintone API 呼び出しを含むため余裕を持たせる。
     *
     * @var int|null
     */
    public ?int $timeout = 60;

    /**
     * 失敗時の最大リトライ回数（初回実行を含めると最大 3 回実行される）。
     *
     * @var int|null
     */
    public ?int $retries = 2;

    /**
     * @param array<string, mixed> $data ジョブデータ (app_id / record_id / event_type)
     * @param int $jobId QueuedJob の ID
     * @return void
     */
    public function run(array $data, int $jobId): void
    {
        $appId = (int)($data['app_id'] ?? 0);
        $recordId = (int)($data['record_id'] ?? 0);
        $eventType = (string)($data['event_type'] ?? '');

        if ($appId === 0 || $recordId === 0 || $eventType === '') {
            throw new RuntimeException(
                "Invalid job data: app_id={$appId} record_id={$recordId} event_type={$eventType}",
            );
        }

        $processor = $this->resolveProcessor($appId);
        $processor->process($appId, $recordId, $eventType);
    }

    /**
     * app_id に対応するプロセッサを config から取得してインスタンス化する。
     *
     * @param int $appId kintone アプリID
     * @return \App\Service\Kintone\AbstractKintoneWebhookProcessor
     * @throws \RuntimeException プロセッサが未設定または不正な場合
     */
    private function resolveProcessor(int $appId): AbstractKintoneWebhookProcessor
    {
        /** @var string|null $processorClass */
        $processorClass = Configure::read("Cybozu.webhook.apps.{$appId}.processor");

        if (empty($processorClass)) {
            throw new RuntimeException(
                "Cybozu.webhook.apps.{$appId}.processor が設定されていません。",
            );
        }

        if (!class_exists($processorClass)) {
            throw new RuntimeException(
                "プロセッサクラスが見つかりません: {$processorClass}",
            );
        }

        $processor = new $processorClass($appId);

        if (!$processor instanceof AbstractKintoneWebhookProcessor) {
            throw new RuntimeException(
                "{$processorClass} は AbstractKintoneWebhookProcessor を継承していません。",
            );
        }

        return $processor;
    }
}
