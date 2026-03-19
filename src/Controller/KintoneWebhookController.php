<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Log\Log;
use Cake\Utility\Text;

/**
 * KintoneWebhook Controller
 *
 * kintone から送信される webhook を受け取り、処理キューに追加する。
 * - AppController を継承しない（認証・CSRF 不要のエンドポイント）
 * - 認証スキップ: Application::middleware() の RolePermissionAuthorizationMiddleware skip に追加済み
 * - CSRF スキップ: Application::middleware() の CsrfProtectionMiddleware skipCheckCallback で除外済み
 *
 * ## kintone webhook 設定
 * - URL:    POST /webhook/kintone
 * - Secret: Cybozu.webhook.token が設定されている場合のみ検証
 *
 * ## 設定例 (config/app_local.php)
 * ```php
 * 'Cybozu' => [
 *     'subdomain' => 'your-subdomain',
 *     // ...既存の oauth / apps 設定...
 *     'webhook' => [
 *         'token' => 'your-secret-token',  // '' で検証スキップ
 *         'apps'  => [
 *             123 => [
 *                 'api_token' => 'your-kintone-api-token',
 *                 'processor' => \App\Service\Kintone\SampleKintoneWebhookProcessor::class,
 *             ],
 *         ],
 *     ],
 * ],
 * ```
 */
class KintoneWebhookController extends Controller
{
    /**
     * kintone webhook 受信
     *
     * POST /webhook/kintone
     *
     * @return \Cake\Http\Response
     */
    public function receive(): Response
    {
        if (!$this->request->is('post')) {
            return $this->jsonResponse(['error' => 'Method Not Allowed'], 405);
        }

        // Webhook トークン検証（設定されている場合のみ）
        $webhookToken = Configure::read('Cybozu.webhook.token');
        if (!empty($webhookToken)) {
            $requestToken = $this->request->getHeaderLine('X-Cybozu-Webhook-Token');
            if (!hash_equals((string)$webhookToken, $requestToken)) {
                Log::warning('KintoneWebhook: invalid token', ['scope' => 'kintone_webhook']);

                return $this->jsonResponse(['error' => 'Unauthorized'], 401);
            }
        }

        // BodyParserMiddleware が解析済みのデータを使う
        /** @var array<string, mixed> $body */
        $body = (array)$this->request->getData();

        $appId = (int)(is_array($body['app'] ?? null) ? ($body['app']['id'] ?? 0) : 0);
        $recordId = (int)(is_array($body['record'] ?? null) && is_array($body['record']['$id'] ?? null)
            ? ($body['record']['$id']['value'] ?? 0)
            : 0);
        $eventType = (string)($body['type'] ?? '');

        if ($appId === 0 || $recordId === 0 || $eventType === '') {
            Log::warning('KintoneWebhook: missing required fields', [
                'scope' => 'kintone_webhook',
                'body' => $body,
            ]);

            return $this->jsonResponse(['error' => 'Bad Request: missing required fields'], 400);
        }

        /** @var \App\Model\Table\KintoneWebhookQueuesTable $queuesTable */
        $queuesTable = $this->fetchTable('KintoneWebhookQueues');

        $queue = $queuesTable->newEntity([
            'id' => Text::uuid(),
            'app_id' => $appId,
            'record_id' => $recordId,
            'event_type' => $eventType,
            'payload' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'pending',
            'attempts' => 0,
        ]);

        if ($queue->getErrors()) {
            Log::error('KintoneWebhook: validation error', [
                'scope' => 'kintone_webhook',
                'errors' => $queue->getErrors(),
            ]);

            return $this->jsonResponse(['error' => 'Bad Request: validation failed'], 400);
        }

        if (!$queuesTable->save($queue)) {
            Log::error('KintoneWebhook: failed to save queue', [
                'scope' => 'kintone_webhook',
                'app_id' => $appId,
                'record_id' => $recordId,
            ]);

            return $this->jsonResponse(['error' => 'Internal Server Error'], 500);
        }

        Log::info('KintoneWebhook: queued', [
            'scope' => 'kintone_webhook',
            'queue_id' => $queue->id,
            'app_id' => $appId,
            'record_id' => $recordId,
            'event_type' => $eventType,
        ]);

        return $this->jsonResponse(['status' => 'queued', 'queue_id' => $queue->id]);
    }

    /**
     * JSON レスポンスを生成する。
     *
     * @param array<string, mixed> $data レスポンスデータ
     * @param int $status HTTPステータスコード
     * @return \Cake\Http\Response
     */
    private function jsonResponse(array $data, int $status = 200): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody((string)json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
