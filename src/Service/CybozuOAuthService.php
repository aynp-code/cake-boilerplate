<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\CybozuAuth;
use App\Model\Table\CybozuAuthsTable;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use RuntimeException;

/**
 * Cybozu OAuth トークン管理サービス
 *
 * ## 責務
 *   - access_token / refresh_token のライフサイクル管理（取得・保存・更新・削除）
 *   - 認可 URL の生成
 *   - 認可コードをトークンに交換する OAuth フロー
 *
 * ## 責務外（分離済み）
 *   - kintone REST API の呼び出し → KintoneApiClient
 *   - whoami アプリを使った本人確認 → KintoneWhoAmIService
 *
 * ## トークンライフサイクル
 *
 *  getValidToken($userId)
 *    → cybozu_auths にレコードがある
 *        → access_token が有効期限内 → そのまま返す
 *        → 期限切れ              → refreshToken() で更新して返す
 *        → refresh も失敗        → null（呼び出し元が再 OAuth へ誘導）
 *    → レコードがない            → null（呼び出し元が /auth/cybozu/connect へ誘導）
 */
class CybozuOAuthService
{
    private const SCOPE = 'k:app_record:read k:app_record:write';

    /**
     * @param string $subdomain      例: 'example'（https://example.cybozu.com の場合）
     * @param string $clientId       OAuth クライアント ID
     * @param string $clientSecret   OAuth クライアントシークレット
     * @param string $redirectUri    コールバック URL
     */
    public function __construct(
        private readonly string $subdomain,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
    ) {
    }

    // =========================================================================
    // トークン取得・管理
    // =========================================================================

    /**
     * 有効な access_token を返す。
     *
     * - 期限内のトークンがあればそのまま返す
     * - 期限切れなら refresh_token で自動更新して返す
     * - 更新失敗 / レコードなし → null（呼び出し元が再 OAuth を促す）
     *
     * @param string $userId  users.id (UUID)
     * @return string|null  有効な access_token、または null
     */
    public function getValidToken(string $userId): ?string
    {
        $auth = $this->authsTable()->findByUserId($userId);

        if ($auth === null) {
            return null;
        }

        if ($auth->isAccessTokenValid()) {
            return $auth->access_token;
        }

        try {
            $auth = $this->refreshToken($auth);

            return $auth->access_token;
        } catch (\Throwable $e) {
            Log::warning("Cybozu token refresh failed for user {$userId}: " . $e->getMessage(), ['scope' => 'cybozu']);

            return null;
        }
    }

    /**
     * コールバック後にトークンを cybozu_auths へ保存（upsert）する。
     *
     * @param string $userId
     * @param array{access_token:string, refresh_token:string, expires_in:int, scope:string} $tokenData
     * @return CybozuAuth
     */
    public function saveToken(string $userId, array $tokenData): CybozuAuth
    {
        $expiresAt = DateTime::now()->modify("+{$tokenData['expires_in']} seconds");

        return $this->authsTable()->upsertForUser($userId, [
            'access_token'  => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'expires_at'    => $expiresAt,
            'scope'         => $tokenData['scope'] ?? null,
        ]);
    }

    /**
     * cybozu_auths のレコードを削除する（連携解除）。
     */
    public function revokeToken(string $userId): void
    {
        $auth = $this->authsTable()->findByUserId($userId);
        if ($auth !== null) {
            $this->authsTable()->delete($auth);
        }
    }

    /**
     * サブドメインを返す（KintoneApiClient を直接生成したい場合に使用）
     */
    public function getSubdomain(): string
    {
        return $this->subdomain;
    }

    // =========================================================================
    // OAuth フロー
    // =========================================================================

    /**
     * 認可 URL を生成する。
     *
     * @param string $state  CSRF 対策用ランダム文字列（呼び出し元でセッションに保存）
     */
    public function buildAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'state'         => $state,
            'response_type' => 'code',
            'scope'         => self::SCOPE,
        ], encoding_type: PHP_QUERY_RFC3986);

        return "https://{$this->subdomain}.cybozu.com/oauth2/authorization?{$params}";
    }

    /**
     * 認可コードをトークンに交換する。
     *
     * @param string $code
     * @return array{access_token:string, refresh_token:string, expires_in:int, scope:string}
     * @throws RuntimeException
     */
    public function fetchToken(string $code): array
    {
        $url  = "https://{$this->subdomain}.cybozu.com/oauth2/token";
        $body = http_build_query([
            'grant_type'   => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code'         => $code,
        ]);

        return $this->doTokenRequest($url, $body);
    }

    /**
     * 有効なアクセストークンから KintoneApiClient を生成して返す。
     *
     * 今後のkintoneアプリ追加では以下のパターンで使用する:
     *
     *   $client = $cybozuOAuthService->makeKintoneClient($userId);
     *   $myAppService = new MyKintoneAppService($appId);
     *   $result = $myAppService->doSomething($client, ...);
     *
     * @throws RuntimeException トークンが取得できない場合
     */
    public function makeKintoneClient(string $userId): KintoneApiClientInterface
    {
        $token = $this->getValidToken($userId);

        if ($token === null) {
            throw new RuntimeException("No valid Cybozu token for user {$userId}.");
        }

        return new KintoneApiClient($this->subdomain, $token);
    }

    // =========================================================================
    // private: トークン管理
    // =========================================================================

    /**
     * refresh_token で access_token を更新し、DB を上書きして返す。
     *
     * cybozu のリフレッシュレスポンスには refresh_token が含まれない場合があるため、
     * 空の場合は既存の refresh_token を保持する。
     *
     * @throws RuntimeException  refresh 失敗時
     */
    private function refreshToken(CybozuAuth $auth): CybozuAuth
    {
        $url  = "https://{$this->subdomain}.cybozu.com/oauth2/token";
        $body = http_build_query([
            'grant_type'    => 'refresh_token',
            'refresh_token' => $auth->refresh_token,
        ]);

        $tokenData = $this->doTokenRequest($url, $body);
        $expiresAt = DateTime::now()->modify("+{$tokenData['expires_in']} seconds");

        $auth = $this->authsTable()->patchEntity($auth, [
            'access_token'  => $tokenData['access_token'],
            // refresh_token はレスポンスに含まれない場合があるため、空なら既存の値を保持する
            'refresh_token' => $tokenData['refresh_token'] !== '' ? $tokenData['refresh_token'] : $auth->refresh_token,
            'expires_at'    => $expiresAt,
            'scope'         => $tokenData['scope'] ?? $auth->scope,
        ]);

        if (!$this->authsTable()->save($auth)) {
            throw new RuntimeException(
                'Failed to save refreshed token: ' . json_encode($auth->getErrors(), JSON_UNESCAPED_UNICODE)
            );
        }

        Log::info("Cybozu token refreshed for cybozu_auth id={$auth->id}", ['scope' => 'cybozu']);

        return $auth;
    }

    /**
     * token エンドポイントへリクエストし、token データを返す共通処理。
     *
     * @return array{access_token:string, refresh_token:string, expires_in:int, scope:string}
     * @throws RuntimeException
     */
    private function doTokenRequest(string $url, string $body): array
    {
        $opts = [
            'http' => [
                'method'        => 'POST',
                'header'        => implode("\r\n", [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Authorization: Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
                ]),
                'content'       => $body,
                'ignore_errors' => true,
                'timeout'       => 15,
            ],
        ];

        $context = stream_context_create($opts);
        $raw     = @file_get_contents($url, false, $context);

        if ($raw === false) {
            throw new RuntimeException("Cybozu token request failed: POST {$url}");
        }

        $response = json_decode($raw ?: '{}', true);

        if (!is_array($response)) {
            throw new RuntimeException('Cybozu token response is not valid JSON.');
        }

        if (isset($response['error'])) {
            Log::error('Cybozu token error: ' . json_encode($response), ['scope' => 'cybozu']);
            throw new RuntimeException(
                "Cybozu token error: {$response['error']} - " . ($response['error_description'] ?? '')
            );
        }

        if (empty($response['access_token'])) {
            throw new RuntimeException('access_token not found in token response.');
        }

        return [
            'access_token'  => (string)$response['access_token'],
            'refresh_token' => (string)($response['refresh_token'] ?? ''),
            'expires_in'    => (int)($response['expires_in'] ?? 3600),
            'scope'         => (string)($response['scope'] ?? ''),
        ];
    }

    // =========================================================================
    // private: TableRegistry
    // =========================================================================

    private function authsTable(): CybozuAuthsTable
    {
        /** @var CybozuAuthsTable */
        return TableRegistry::getTableLocator()->get('CybozuAuths');
    }
}