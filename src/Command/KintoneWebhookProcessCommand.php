<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Table\KintoneWebhookQueuesTable;
use App\Service\Kintone\AbstractKintoneWebhookProcessor;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;
use Throwable;

/**
 * KintoneWebhookProcess Command
 *
 * kintone_webhook_queues テーブルの pending ジョブを処理する。
 * cron などで定期実行してください。
 *
 * ## 使い方
 * ```bash
 * bin/cake kintone_webhook_process              # pending を最大 20 件処理
 * bin/cake kintone_webhook_process --limit 50   # 最大 50 件
 * bin/cake kintone_webhook_process --app-id 123 # 特定アプリのみ
 * ```
 *
 * ## リトライ
 * - 失敗時: attempts をインクリメントし、次回実行は 5分後 (scheduled_at) に設定
 * - KintoneWebhookQueuesTable::MAX_ATTEMPTS 回失敗すると status = failed で停止
 *
 * ## プロセッサ登録 (config/app_local.php)
 * ```php
 * 'Cybozu' => [
 *     'subdomain' => 'your-subdomain',
 *     'webhook' => [
 *         'token' => 'secret',
 *         'apps' => [
 *             123 => [
 *                 'api_token' => 'your-token',
 *                 'processor' => \App\Service\Kintone\SampleKintoneWebhookProcessor::class,
 *             ],
 *         ],
 *     ],
 * ],
 * ```
 */
class KintoneWebhookProcessCommand extends Command
{
    use LocatorAwareTrait;

    /** リトライ間隔（分） */
    private const RETRY_INTERVAL_MINUTES = 5;

    /** processing のまま stuck したと見なすタイムアウト（分） */
    private const STUCK_TIMEOUT_MINUTES = 30;

    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'kintone_webhook_process';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser オプションパーサー
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('kintone webhook キューを処理します。')
            ->addOption('limit', [
                'short' => 'l',
                'help' => '1回の実行で処理する最大件数',
                'default' => '20',
            ])
            ->addOption('app-id', [
                'short' => 'a',
                'help' => '処理対象の kintone アプリID（省略時は全アプリ）',
            ]);

        return $parser;
    }

    /**
     * @param \Cake\Console\Arguments $args コマンド引数
     * @param \Cake\Console\ConsoleIo $io コンソールIO
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $limit = (int)$args->getOption('limit');
        $appId = $args->getOption('app-id') !== null ? (int)$args->getOption('app-id') : null;

        /** @var \App\Model\Table\KintoneWebhookQueuesTable $queuesTable */
        $queuesTable = $this->fetchTable('KintoneWebhookQueues');

        // stuck（processing のまま長時間止まっている）ジョブを pending に戻す
        $this->rescueStuckJobs($queuesTable, $io);

        // pending ジョブを取得
        $query = $queuesTable->find()
            ->where([
                'status' => KintoneWebhookQueuesTable::STATUS_PENDING,
                'OR' => [
                    'scheduled_at IS NULL',
                    'scheduled_at <=' => DateTime::now(),
                ],
            ])
            ->orderBy(['created' => 'ASC'])
            ->limit($limit);

        if ($appId !== null) {
            $query->andWhere(['app_id' => $appId]);
        }

        /** @var array<\App\Model\Entity\KintoneWebhookQueue> $jobs */
        $jobs = $query->all()->toArray();

        if (empty($jobs)) {
            $io->verbose('処理対象のキューはありません。');

            return self::CODE_SUCCESS;
        }

        $io->out(sprintf('%d 件のジョブを処理します。', count($jobs)));

        $done = 0;
        $failed = 0;

        foreach ($jobs as $job) {
            // processing に更新（重複実行防止）
            $job->status = KintoneWebhookQueuesTable::STATUS_PROCESSING;
            $queuesTable->save($job);

            try {
                $processor = $this->resolveProcessor($job->app_id);
                $processor->process($job);

                $job->status = KintoneWebhookQueuesTable::STATUS_DONE;
                $job->processed_at = DateTime::now();
                $job->error_message = null;
                $queuesTable->save($job);

                $io->verbose(sprintf(
                    '  [OK] queue_id=%s app=%d record=%d event=%s',
                    $job->id,
                    $job->app_id,
                    $job->record_id,
                    $job->event_type,
                ));
                $done++;
            } catch (Throwable $e) {
                $attempts = $job->attempts + 1;
                $isFinal = $attempts >= KintoneWebhookQueuesTable::MAX_ATTEMPTS;

                $job->attempts = $attempts;
                $job->error_message = substr($e->getMessage(), 0, 2000);
                $job->status = $isFinal
                    ? KintoneWebhookQueuesTable::STATUS_FAILED
                    : KintoneWebhookQueuesTable::STATUS_PENDING;
                $job->scheduled_at = $isFinal
                    ? null
                    : DateTime::now()->modify('+' . self::RETRY_INTERVAL_MINUTES . ' minutes');
                $queuesTable->save($job);

                $io->error(sprintf(
                    '  [FAIL] queue_id=%s app=%d record=%d event=%s attempts=%d/%d: %s',
                    $job->id,
                    $job->app_id,
                    $job->record_id,
                    $job->event_type,
                    $attempts,
                    KintoneWebhookQueuesTable::MAX_ATTEMPTS,
                    $e->getMessage(),
                ));
                $failed++;
            }
        }

        $io->out(sprintf('完了: %d 件成功 / %d 件失敗', $done, $failed));

        return $failed > 0 ? self::CODE_ERROR : self::CODE_SUCCESS;
    }

    /**
     * app_id に対応するプロセッサを config から取得してインスタンス化する。
     *
     * @param int $appId kintone アプリID
     * @return \App\Service\Kintone\AbstractKintoneWebhookProcessor
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

    /**
     * processing のまま STUCK_TIMEOUT_MINUTES 分以上経過したジョブを pending に戻す。
     *
     * @param \App\Model\Table\KintoneWebhookQueuesTable $queuesTable キューテーブル
     * @param \Cake\Console\ConsoleIo $io コンソールIO
     * @return void
     */
    private function rescueStuckJobs(KintoneWebhookQueuesTable $queuesTable, ConsoleIo $io): void
    {
        $cutoff = DateTime::now()->modify('-' . self::STUCK_TIMEOUT_MINUTES . ' minutes');

        $stuckCount = $queuesTable->updateAll(
            ['status' => KintoneWebhookQueuesTable::STATUS_PENDING, 'scheduled_at' => null],
            [
                'status' => KintoneWebhookQueuesTable::STATUS_PROCESSING,
                'modified <=' => $cutoff,
            ],
        );

        if ($stuckCount > 0) {
            $io->warning(sprintf('stuck ジョブを %d 件 pending に戻しました。', $stuckCount));
        }
    }
}
