<?php
declare(strict_types=1);

namespace App\Service\Kintone;

use App\Model\Entity\KintoneWebhookQueue;
use App\Service\KintoneApiClient;
use App\Service\KintoneApiClientInterface;
use Cake\Core\Configure;
use RuntimeException;

/**
 * kintone Webhook プロセッサ 基底クラス
 *
 * kintone から受け取った webhook キューを処理する。
 * 新しいアプリを追加する場合はこのクラスを継承して
 * appId() と upsert() を実装してください。
 *
 * ## 実装例
 * ```php
 * class MyKintoneWebhookProcessor extends AbstractKintoneWebhookProcessor
 * {
 *     protected function appId(): int { return 456; }
 *
 *     protected function upsert(array $record): void
 *     {
 *         // kintone レコードをローカル DB に保存
 *         $recordNumber = $this->value($record, 'レコード番号');
 *         ...
 *     }
 * }
 * ```
 *
 * ## 設定 (config/app_local.php)
 * ```php
 * 'KintoneWebhook' => [
 *     'apps' => [
 *         456 => [
 *             'subdomain' => 'xxx',
 *             'api_token' => 'xxx',
 *             'processor' => \App\Service\Kintone\MyKintoneWebhookProcessor::class,
 *         ],
 *     ],
 * ],
 * ```
 */
abstract class AbstractKintoneWebhookProcessor
{
    // =========================================================================
    // 子クラスで実装するメソッド
    // =========================================================================

    /**
     * kintone アプリID を返す。
     *
     * @return int
     */
    abstract protected function appId(): int;

    /**
     * kintone レコードをローカル DB に upsert する。
     *
     * @param array<string, mixed> $record kintone API レスポンスの record オブジェクト
     */
    abstract protected function upsert(array $record): void;

    // =========================================================================
    // テンプレートメソッド（通常はオーバーライド不要）
    // =========================================================================

    /**
     * キュージョブを処理する。
     *
     * - DELETE イベント: ローカル DB からレコードを削除
     * - その他:          kintone API でレコードを取得して upsert
     */
    public function process(KintoneWebhookQueue $job): void
    {
        if ($job->event_type === 'APP.RECORD.DELETE') {
            $this->handleDelete($job);

            return;
        }

        $client = $this->createClient();
        $record = $this->fetchRecord($client, $job->record_id);
        $this->upsert($record);
    }

    /**
     * DELETE イベントの処理。
     * デフォルトは何もしない（必要に応じてオーバーライド）。
     */
    protected function handleDelete(KintoneWebhookQueue $job): void
    {
        // サブクラスで override して DELETE 処理を実装すること
        // 例: $this->fetchTable('KintoneWebhookRecords')->deleteAll(['kintone_record_number' => ...])
    }

    // =========================================================================
    // protected ヘルパー
    // =========================================================================

    /**
     * 設定からAPIトークン認証済みクライアントを生成する。
     *
     * subdomain は Cybozu.subdomain（共通）を使用し、
     * api_token は Cybozu.webhook.apps.{appId}.api_token を使用する。
     */
    protected function createClient(): KintoneApiClientInterface
    {
        $appId = $this->appId();
        $subdomain = (string)Configure::read('Cybozu.subdomain');
        /** @var array<string, string>|null $appConfig */
        $appConfig = Configure::read("Cybozu.webhook.apps.{$appId}");

        if ($subdomain === '' || empty($appConfig['api_token'])) {
            throw new RuntimeException(
                "kintone webhook config for app {$appId} is incomplete. "
                . 'Check Cybozu.subdomain and Cybozu.webhook.apps config in app_local.php.',
            );
        }

        return new KintoneApiClient(
            subdomain: $subdomain,
            accessToken: $appConfig['api_token'],
            useApiToken: true,
        );
    }

    /**
     * kintone API からレコードを1件取得する。
     *
     * @return array<string, mixed>
     * @throws \RuntimeException レコードが存在しない場合
     */
    protected function fetchRecord(KintoneApiClientInterface $client, int $recordId): array
    {
        $response = $client->get('/k/v1/record.json', [
            'app' => $this->appId(),
            'id' => $recordId,
        ]);

        $record = $response['record'] ?? null;

        if (!is_array($record)) {
            throw new RuntimeException(
                "Record {$recordId} not found in kintone app {$this->appId()}.",
            );
        }

        return $record;
    }

    /**
     * kintone レコードのフィールド値を安全に取り出す。
     *
     * @param array<string, mixed> $record
     */
    protected function value(array $record, string $fieldCode, mixed $default = null): mixed
    {
        return $record[$fieldCode]['value'] ?? $default;
    }

    /**
     * CREATOR / MODIFIER フィールドの表示名を取り出す。
     *
     * @param array<string, mixed> $record
     */
    protected function userDisplayName(array $record, string $fieldCode): ?string
    {
        $value = $record[$fieldCode]['value'] ?? null;
        if (!is_array($value)) {
            return null;
        }

        return isset($value['name']) ? (string)$value['name'] : null;
    }

    /**
     * CHECK_BOX フィールドの値を string[] として取り出す。
     *
     * @param array<string, mixed> $record
     * @return array<int, string>
     */
    protected function checkboxValues(array $record, string $fieldCode): array
    {
        $value = $record[$fieldCode]['value'] ?? [];

        return is_array($value)
            ? array_values(array_map('strval', $value))
            : [];
    }

    /**
     * FILE フィールドの添付ファイル情報を配列として取り出す。
     *
     * @param array<string, mixed> $record
     * @return array<int, array<string, string>>
     */
    protected function attachmentValues(array $record, string $fieldCode): array
    {
        $value = $record[$fieldCode]['value'] ?? [];
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_map(function (mixed $file): array {
            if (!is_array($file)) {
                return [];
            }

            return [
                'fileKey' => (string)($file['fileKey'] ?? ''),
                'name' => (string)($file['name'] ?? ''),
                'size' => (string)($file['size'] ?? ''),
                'contentType' => (string)($file['contentType'] ?? ''),
            ];
        }, $value));
    }
}
