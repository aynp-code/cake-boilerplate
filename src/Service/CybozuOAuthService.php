<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\CybozuAuth;
use App\Model\Table\CybozuAuthsTable;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use RuntimeException;

/**
 * Cybozu OAuth 連携サービス
 *
 * ## トークンライフサイクル
 *
 *  getValidToken($userId)
 *    → cybozu_auths にレコードがある
 *        → access_token が有効期限内 → そのまま返す
 *        → 期限切れ              → refreshToken() で更新して返す
 *        → refresh も失敗        → null（呼び出し元が再 OAuth へ誘導）
 *    → レコードがない            → null（呼び出し元が /auth/cybozu/connect へ誘導）
 *
 * ## OAuth フロー（初回 or 再認可）
 *
 *  buildAuthorizationUrl($state)  → ブラウザを cybozu 認可画面へ
 *  fetchToken($code)              → code → access_token / refresh_token
 *  resolveLoginCode($at)   → レコード追加 → $creator.code 取得 → レコード削除
 *  saveToken($userId, $tokenData) → cybozu_auths に保存（upsert）
 */
class CybozuOAuthService
{
    private string $subdomain;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $appId;

    private const SCOPE = 'k:app_record:read k:app_record:write';

    public function __construct()
    {
        $config = Configure::read('Cybozu');

        if (
            empty($config['subdomain'])
            || empty($config['client_id'])
            || empty($config['client_secret'])
            || empty($config['redirect_uri'])
            || empty($config['app_id'])
        ) {
            throw new RuntimeException('Cybozu configuration is incomplete. Check app_local.php Kintone section.');
        }

        $this->subdomain    = $config['subdomain'];
        $this->clientId     = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->redirectUri  = $config['redirect_uri'];
        $this->appId        = $config['app_id'];
    }

    // =========================================================================
    // トークン取得・管理（呼び出し元が最初に使うメソッド）
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
            return null; // 未連携
        }

        if ($auth->isAccessTokenValid()) {
            return $auth->access_token;
        }

        // access_token 期限切れ → refresh を試みる
        try {
            $auth = $this->refreshToken($auth);
            return $auth->access_token;
        } catch (RuntimeException $e) {
            Log::warning("Cybozu token refresh failed for user {$userId}: " . $e->getMessage(), ['scope' => 'cybozu']);
            return null; // 呼び出し元が再 OAuth へ誘導
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
     *
     * @param string $userId
     * @return void
     */
    public function revokeToken(string $userId): void
    {
        $auth = $this->authsTable()->findByUserId($userId);
        if ($auth !== null) {
            $this->authsTable()->delete($auth);
        }
    }

    // =========================================================================
    // OAuth フロー
    // =========================================================================

    /**
     * 認可 URL を生成する。
     *
     * @param string $state  CSRF 対策用ランダム文字列（呼び出し元でセッションに保存）
     * @return string
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
     * アクセストークンを使ってレコードを追加し、$creator.code を返す。
     * 確認後はレコードを削除してクリーンアップします。
     *
     * @param string $accessToken
     * @return string  kintone ログインコード
     * @throws RuntimeException
     */
    public function resolveLoginCode(string $accessToken, string $username): string
    {
        $recordId = $this->addRecord($accessToken, $username);

        try {
            $creatorCode = $this->getCreatorCode($accessToken, $recordId);
        } finally {
            try {
                $this->deleteRecord($accessToken, $recordId);
            } catch (\Throwable $e) {
                Log::warning('Kintone deleteRecord failed: ' . $e->getMessage(), ['scope' => 'cybozu']);
            }
        }

        if ($creatorCode === '') {
            throw new RuntimeException('Could not resolve $creator.code from kintone record.');
        }

        return $creatorCode;
    }

    // =========================================================================
    // private: トークン管理
    // =========================================================================

    /**
     * refresh_token で access_token を更新し、DB を上書きして返す。
     *
     * @param CybozuAuth $auth
     * @return CybozuAuth  更新済みエンティティ
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
            'refresh_token' => $tokenData['refresh_token'],
            'expires_at'    => $expiresAt,
            'scope'         => $tokenData['scope'] ?? $auth->scope,
        ]);

        if (!$this->authsTable()->save($auth)) {
            throw new RuntimeException('Failed to save refreshed token.');
        }

        Log::info("Cybozu token refreshed for cybozu_auth id={$auth->id}", ['scope' => 'cybozu']);

        return $auth;
    }

    /**
     * token エンドポイントを叩いて token データを返す共通処理。
     *
     * @return array{access_token:string, refresh_token:string, expires_in:int, scope:string}
     * @throws RuntimeException
     */
    private function doTokenRequest(string $url, string $body): array
    {
        $response = $this->httpRequest('POST', $url, $body, [
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
        ]);

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
    // private: kintone Record API
    // =========================================================================

    private function addRecord(string $accessToken, string $username): string
    {
        $url = "https://{$this->subdomain}.cybozu.com/k/v1/record.json";

        $body = (string)json_encode([
            'app'    => (int)$this->appId,
            'record' => [
                'login_id' => ['value' => $username],
            ],
        ]);

        $response = $this->httpRequest('POST', $url, $body, [
            'Content-Type'  => 'application/json',
            'Authorization' => "Bearer {$accessToken}",
        ]);

        if (isset($response['message']) || isset($response['errors'])) {
            Log::error('Kintone add record error: ' . json_encode($response), ['scope' => 'cybozu']);
            throw new RuntimeException('Failed to add kintone record: ' . ($response['message'] ?? json_encode($response)));
        }

        $recordId = (string)($response['id'] ?? '');
        if ($recordId === '') {
            throw new RuntimeException('record id not found in add-record response.');
        }

        return $recordId;
    }

    private function getCreatorCode(string $accessToken, string $recordId): string
    {
        $url = "https://{$this->subdomain}.cybozu.com/k/v1/record.json?" . http_build_query([
            'app' => (int)$this->appId,
            'id'  => (int)$recordId,
        ]);

        $response = $this->httpRequest('GET', $url, null, [
            'Authorization' => "Bearer {$accessToken}",
        ]);

        if (isset($response['message'])) {
            throw new RuntimeException('Failed to get kintone record: ' . $response['message']);
        }

        $record = $response['record'] ?? [];

        // kintone は CREATOR 型フィールドを {"type":"CREATOR","value":{"code":"xxx","name":"yyy"}} で返す
        // フィールドコードが日本語（"作成者"）の場合も含め、type=CREATOR のフィールドを探す
        foreach ($record as $fieldCode => $field) {
            if (($field['type'] ?? '') === 'CREATOR') {
                $code = (string)($field['value']['code'] ?? '');
                if ($code !== '') {
                    return $code;
                }
            }
        }

        throw new RuntimeException(
            'CREATOR field not found in kintone record. Available fields: ' . implode(', ', array_keys($record))
        );
    }

    private function deleteRecord(string $accessToken, string $recordId): void
    {
        // kintone DELETE API は JSON ボディで app・ids[] を渡す
        // クエリ文字列方式だと ids[0] がエンコードされて正しく送れない
        $url  = "https://{$this->subdomain}.cybozu.com/k/v1/records.json";
        $body = (string)json_encode([
            'app' => (int)$this->appId,
            'ids' => [(int)$recordId],
        ]);

        $this->httpRequest('DELETE', $url, $body, [
            'Content-Type'  => 'application/json',
            'Authorization' => "Bearer {$accessToken}",
        ]);
    }

    // =========================================================================
    // private: HTTP
    // =========================================================================

    /**
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    private function httpRequest(string $method, string $url, ?string $body, array $headers): array
    {
        $opts = [
            'http' => [
                'method'        => $method,
                'header'        => $this->buildHeaderString($headers),
                'ignore_errors' => true,
                'timeout'       => 15,
            ],
        ];

        if ($body !== null) {
            $opts['http']['content'] = $body;
        }

        $context = stream_context_create($opts);
        $raw     = @file_get_contents($url, false, $context);

        if ($raw === false) {
            throw new RuntimeException("HTTP request failed: {$method} {$url}");
        }

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, string> $headers
     */
    private function buildHeaderString(array $headers): string
    {
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = "{$name}: {$value}";
        }
        return implode("\r\n", $lines);
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