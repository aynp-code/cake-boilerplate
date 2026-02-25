<?php
declare(strict_types=1);

namespace App\Service;

/**
 * kintone REST API クライアントのインターフェース
 *
 * 今後のアプリ追加（在庫管理・申請など）では、このインターフェースを
 * 受け取る専用サービスを作ることで CybozuOAuthService に触れずに済む。
 */
interface KintoneApiClientInterface
{
    /**
     * GETリクエスト
     *
     * @param string $path  例: '/k/v1/record.json'
     * @param array<string, mixed> $query クエリパラメータ
     * @return array<string, mixed>
     * @throws \RuntimeException
     */
    public function get(string $path, array $query = []): array;

    /**
     * POSTリクエスト
     *
     * @param string $path
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     * @throws \RuntimeException
     */
    public function post(string $path, array $body): array;

    /**
     * DELETEリクエスト
     *
     * @param string $path
     * @param array<string, mixed> $body
     * @return void
     * @throws \RuntimeException
     */
    public function delete(string $path, array $body): void;
}
