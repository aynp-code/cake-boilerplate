<?php
declare(strict_types=1);

namespace App\Service\Kintone;

use App\Service\KintoneApiClientInterface;
use RuntimeException;

/**
 * kintone アプリ サービス 共通基盤
 *
 * ## 使い方
 *
 * 新しい kintone アプリを追加する場合は、このクラスを継承して
 * 以下の3メソッドだけを実装してください。
 *
 *   - appId()         : kintone アプリ ID
 *   - toRecord()      : kintone レコード配列 → アプリ内で使う配列
 *   - toKintoneFields(): アプリ内の配列 → kintone フィールド形式
 *
 * ## CRUD
 *
 *   $service = new SampleKintoneService();
 *   $client  = $cybozuOAuthService->makeKintoneClient($userId);
 *
 *   $list    = $service->findAll($client);
 *   $record  = $service->find($client, 42);
 *   $id      = $service->create($client, [...]);
 *   $service->update($client, 42, [...]);
 *   $service->delete($client, 42);
 *
 * ## 添付ファイル
 *
 *   // アップロード（複数可）
 *   $fileKeys = $service->uploadFiles($client, $uploadedFiles);
 *   // $data['attachments'] に fileKey の配列を渡すと toKintoneFields() で送信される
 */
abstract class AbstractKintoneAppService
{
    // =========================================================================
    // 子クラスで実装するメソッド
    // =========================================================================

    abstract protected function appId(): int;

    /**
     * @param array<string, mixed> $kintoneRecord
     * @return array<string, mixed>
     */
    abstract protected function toRecord(array $kintoneRecord): array;

    /**
     * @param array<string, mixed> $data
     * @return array<string, array{value: mixed}>
     */
    abstract protected function toKintoneFields(array $data): array;

    // =========================================================================
    // CRUD 共通実装
    // =========================================================================

    /**
     * 1件取得
     *
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    public function find(KintoneApiClientInterface $client, int $recordId): array
    {
        $response = $client->get('/k/v1/record.json', [
            'app' => $this->appId(),
            'id'  => $recordId,
        ]);

        $record = $response['record'] ?? null;

        if (!is_array($record)) {
            throw new RuntimeException("Record {$recordId} not found in kintone app {$this->appId()}.");
        }

        return $this->toRecord($record);
    }

    /**
     * 一覧取得
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(
        KintoneApiClientInterface $client,
        string $query = '',
        int $limit = 100,
        int $offset = 0,
    ): array {
        $params = [
            'app'        => $this->appId(),
            'totalCount' => 'true',
        ];

        $params['query'] = $query !== ''
            ? $query . " limit {$limit} offset {$offset}"
            : "limit {$limit} offset {$offset}";

        $response = $client->get('/k/v1/records.json', $params);
        $records  = $response['records'] ?? [];

        if (!is_array($records)) {
            return [];
        }

        return array_map(fn(array $r) => $this->toRecord($r), $records);
    }

    /**
     * 件数取得
     */
    public function count(KintoneApiClientInterface $client, string $query = ''): int
    {
        $params = [
            'app'        => $this->appId(),
            'totalCount' => 'true',
            'query'      => $query !== '' ? $query . ' limit 1' : 'limit 1',
        ];

        return (int)($client->get('/k/v1/records.json', $params)['totalCount'] ?? 0);
    }

    /**
     * 作成
     *
     * @param array<string, mixed> $data
     * @return int 作成されたレコードID
     * @throws RuntimeException
     */
    public function create(KintoneApiClientInterface $client, array $data): int
    {
        $response = $client->post('/k/v1/record.json', [
            'app'    => $this->appId(),
            'record' => $this->toKintoneFields($data),
        ]);

        $id = $response['id'] ?? null;

        if ($id === null) {
            throw new RuntimeException('record id not found in create response.');
        }

        return (int)$id;
    }

    /**
     * 更新
     *
     * kintone のレコード更新は PUT メソッドを使う。
     * POST で id を渡しても新規作成になるため注意。
     *
     * @param array<string, mixed> $data 更新するフィールドのみ渡せばよい（部分更新）
     * @throws RuntimeException
     */
    public function update(KintoneApiClientInterface $client, int $recordId, array $data): void
    {
        $client->put('/k/v1/record.json', [
            'app'    => $this->appId(),
            'id'     => $recordId,
            'record' => $this->toKintoneFields($data),
        ]);
    }

    /**
     * 削除
     *
     * @throws RuntimeException
     */
    public function delete(KintoneApiClientInterface $client, int $recordId): void
    {
        $client->delete('/k/v1/records.json', [
            'app' => $this->appId(),
            'ids' => [$recordId],
        ]);
    }

    /**
     * 添付ファイルをアップロードして fileKey の配列を返す
     *
     * CakePHP の $this->request->getUploadedFiles() で取得した
     * UploadedFileInterface の配列をそのまま渡してください。
     *
     * @param array<int, \Psr\Http\Message\UploadedFileInterface> $uploadedFiles
     * @return array<int, string>  fileKey の配列
     * @throws RuntimeException
     */
    public function uploadFiles(KintoneApiClientInterface $client, array $uploadedFiles): array
    {
        $fileKeys = [];

        foreach ($uploadedFiles as $uploadedFile) {
            // エラーがある・空のファイルはスキップ
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                continue;
            }

            $clientFilename = $uploadedFile->getClientFilename() ?? 'file';
            $clientMimeType = $uploadedFile->getClientMediaType() ?? 'application/octet-stream';

            // CakePHP の UploadedFile は getStream() でストリームを取得できるが
            // curl の CURLFile はファイルパスが必要なため一時ファイルに書き出す
            $tmpPath = tempnam(sys_get_temp_dir(), 'kintone_upload_');
            if ($tmpPath === false) {
                throw new RuntimeException('Failed to create temp file for upload.');
            }

            try {
                $uploadedFile->moveTo($tmpPath);
                $result     = $client->postFile($tmpPath, $clientFilename, $clientMimeType);
                $fileKeys[] = $result['fileKey'];
            } finally {
                // 一時ファイルを確実に削除
                if (file_exists($tmpPath)) {
                    unlink($tmpPath);
                }
            }
        }

        return $fileKeys;
    }

    // =========================================================================
    // protected ヘルパー
    // =========================================================================

    /**
     * kintone フィールド値を安全に取り出す
     *
     * @param array<string, mixed> $record
     */
    protected function value(array $record, string $fieldCode, mixed $default = null): mixed
    {
        return $record[$fieldCode]['value'] ?? $default;
    }
}
