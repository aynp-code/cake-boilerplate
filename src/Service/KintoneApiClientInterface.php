<?php
declare(strict_types=1);

namespace App\Service;

/**
 * kintone REST API クライアントのインターフェース
 */
interface KintoneApiClientInterface
{
    /**
     * GETリクエスト
     *
     * @param string $path  例: '/k/v1/record.json'
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     * @throws \RuntimeException
     */
    public function get(string $path, array $query = []): array;

    /**
     * POSTリクエスト（レコード作成・JSON）
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     * @throws \RuntimeException
     */
    public function post(string $path, array $body): array;

    /**
     * PUTリクエスト（レコード更新・JSON）
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     * @throws \RuntimeException
     */
    public function put(string $path, array $body): array;

    /**
     * DELETEリクエスト
     *
     * @param array<string, mixed> $body
     * @throws \RuntimeException
     */
    public function delete(string $path, array $body): void;

    /**
     * ファイルアップロード（multipart/form-data）
     *
     * kintone の添付ファイルアップロードは JSON ではなく multipart で送る必要がある。
     * 成功時は ['fileKey' => '...'] を返す。
     *
     * @param string $filePath  アップロードするファイルのパス
     * @param string $fileName  kintone 上に表示するファイル名
     * @param string $mimeType  MIMEタイプ（例: 'image/png'）
     * @return array{fileKey: string}
     * @throws \RuntimeException
     */
    public function postFile(string $filePath, string $fileName, string $mimeType): array;

    /**
     * ファイルダウンロード
     *
     * kintone の添付ファイルをバイナリデータとして取得する。
     * 戻り値は ['body' => バイナリ文字列, 'contentType' => 'image/png' など]
     *
     * @return array{body: string, contentType: string}
     * @throws \RuntimeException
     */
    public function getFile(string $fileKey): array;
}
