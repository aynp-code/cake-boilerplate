<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Log\Log;
use RuntimeException;
use Throwable;

/**
 * kintone の「whoami アプリ」を使ってログインコードを解決するサービス
 *
 * 処理フロー:
 *   1. whoami アプリにレコードを追加（username フィールドに値をセット）
 *   2. 追加されたレコードの $creator.code（CREATOR フィールド）を取得
 *   3. レコードを削除してクリーンアップ
 *
 * kintone 連携の「本人確認」ロジックを CybozuOAuthService から分離することで、
 * whoami アプリの仕様変更がこのクラスだけで完結する。
 */
class KintoneWhoAmIService
{
    /**
     * @param int $appId The kintone whoami app ID.
     */
    public function __construct(
        private readonly int $appId,
    ) {
    }

    /**
     * アクセストークンを使って kintone ログインコード（$creator.code）を返す。
     *
     * @param \App\Service\KintoneApiClientInterface $client  呼び出し元で生成したクライアント
     * @param string $username  アプリに書き込む login_id の値
     * @return string  kintone ログインコード
     * @throws \RuntimeException
     */
    public function resolveLoginCode(KintoneApiClientInterface $client, string $username): string
    {
        $recordId = $this->addRecord($client, $username);

        try {
            $creatorCode = $this->getCreatorCode($client, $recordId);
        } finally {
            try {
                $this->deleteRecord($client, $recordId);
            } catch (Throwable $e) {
                Log::warning('KintoneWhoAmI: deleteRecord failed: ' . $e->getMessage(), ['scope' => 'cybozu']);
            }
        }

        if ($creatorCode === '') {
            throw new RuntimeException('Could not resolve $creator.code from kintone record.');
        }

        return $creatorCode;
    }

    // =========================================================================
    // private
    // =========================================================================

    /**
     * Add a record to the whoami kintone app.
     *
     * @param \App\Service\KintoneApiClientInterface $client The kintone API client.
     * @param string $username The username to write to the record.
     * @return string The created record ID.
     * @throws \RuntimeException
     */
    private function addRecord(KintoneApiClientInterface $client, string $username): string
    {
        $response = $client->post('/k/v1/record.json', [
            'app' => $this->appId,
            'record' => [
                'login_id' => ['value' => $username],
            ],
        ]);

        $recordId = (string)($response['id'] ?? '');
        if ($recordId === '') {
            throw new RuntimeException('record id not found in add-record response.');
        }

        return $recordId;
    }

    /**
     * Get the CREATOR field code from a kintone record.
     *
     * @param \App\Service\KintoneApiClientInterface $client The kintone API client.
     * @param string $recordId The record ID to retrieve.
     * @return string The creator code.
     * @throws \RuntimeException
     */
    private function getCreatorCode(KintoneApiClientInterface $client, string $recordId): string
    {
        $response = $client->get('/k/v1/record.json', [
            'app' => $this->appId,
            'id' => (int)$recordId,
        ]);

        $record = $response['record'] ?? [];

        // kintone は CREATOR 型フィールドを {"type":"CREATOR","value":{"code":"xxx","name":"yyy"}} で返す
        foreach ($record as $field) {
            if (($field['type'] ?? '') === 'CREATOR') {
                $code = (string)($field['value']['code'] ?? '');
                if ($code !== '') {
                    return $code;
                }
            }
        }

        throw new RuntimeException(
            'CREATOR field not found in kintone record. Available fields: ' . implode(', ', array_keys($record)),
        );
    }

    /**
     * Delete a record from the whoami kintone app.
     *
     * @param \App\Service\KintoneApiClientInterface $client The kintone API client.
     * @param string $recordId The record ID to delete.
     * @return void
     */
    private function deleteRecord(KintoneApiClientInterface $client, string $recordId): void
    {
        $client->delete('/k/v1/records.json', [
            'app' => $this->appId,
            'ids' => [(int)$recordId],
        ]);
    }
}
