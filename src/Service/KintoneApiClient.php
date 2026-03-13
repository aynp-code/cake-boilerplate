<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Log\Log;
use RuntimeException;

/**
 * kintone REST API クライアント
 *
 * Bearer トークンによる認証を担い、GET / POST / PUT / DELETE / postFile を提供する。
 */
class KintoneApiClient implements KintoneApiClientInterface
{
    public function __construct(
        private readonly string $subdomain,
        private readonly string $accessToken,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        $url = $this->buildUrl($path);
        if ($query) {
            $url .= '?' . http_build_query($query, encoding_type: PHP_QUERY_RFC3986);
        }

        return $this->request('GET', $url, null);
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function post(string $path, array $body): array
    {
        return $this->request(
            'POST',
            $this->buildUrl($path),
            (string)json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function put(string $path, array $body): array
    {
        return $this->request(
            'PUT',
            $this->buildUrl($path),
            (string)json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * @param array<string, mixed> $body
     */
    public function delete(string $path, array $body): void
    {
        $this->request(
            'DELETE',
            $this->buildUrl($path),
            (string)json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * ファイルアップロード（multipart/form-data）
     *
     * kintone の添付ファイルは JSON ではなく multipart で送る必要がある。
     * 成功時は ['fileKey' => '...'] を返す。
     *
     * @return array{fileKey: string}
     * @throws RuntimeException
     */
    public function postFile(string $filePath, string $fileName, string $mimeType): array
    {
        $url = $this->buildUrl('/k/v1/file.json');

        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException("curl_init failed: {$url}");
        }

        $curlFile = new \CURLFile($filePath, $mimeType, $fileName);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => ['file' => $curlFile],
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$this->accessToken}",
                'X-Requested-With: XMLHttpRequest',
                // Content-Type は curl が multipart/form-data を自動付与するため指定しない
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $raw     = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException("Kintone file upload failed: {$curlErr}");
        }

        $decoded = json_decode((string)$raw, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Kintone file upload: invalid response.');
        }

        if (isset($decoded['message'])) {
            Log::error(
                'Kintone file upload error: ' . json_encode($decoded, JSON_UNESCAPED_UNICODE),
                ['scope' => 'cybozu']
            );
            throw new RuntimeException('Kintone file upload error: ' . $decoded['message']);
        }

        if (empty($decoded['fileKey'])) {
            throw new RuntimeException('Kintone file upload: fileKey not found in response.');
        }

        return ['fileKey' => (string)$decoded['fileKey']];
    }

    // =========================================================================
    // private
    // =========================================================================

    private function buildUrl(string $path): string
    {
        return "https://{$this->subdomain}.cybozu.com" . $path;
    }

    /**
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    private function request(string $method, string $url, ?string $body): array
    {
        $ch = curl_init($url);

        if ($ch === false) {
            throw new RuntimeException("curl_init failed: {$url}");
        }

        $headers = [
            "Authorization: Bearer {$this->accessToken}",
            'X-Requested-With: XMLHttpRequest',
        ];

        // kintone は GET に Content-Type: application/json があると CB_IL02 を返すため
        // ボディを持つメソッドのみ付与する
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw     = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException("Kintone HTTP request failed: {$method} {$url} curl_error={$curlErr}");
        }

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode((string)$raw, true);

        if (!is_array($decoded)) {
            return [];
        }

        if (isset($decoded['message']) || isset($decoded['errors'])) {
            $detail = $decoded['message'] ?? '';
            if (!empty($decoded['errors'])) {
                $detail .= ' | errors: ' . json_encode($decoded['errors'], JSON_UNESCAPED_UNICODE);
            }
            Log::error(
                "Kintone API error [{$method} {$url}]: " . json_encode($decoded, JSON_UNESCAPED_UNICODE),
                ['scope' => 'cybozu']
            );
            throw new RuntimeException("Kintone API error [{$method} {$url}]: {$detail}");
        }

        return $decoded;
    }
}
