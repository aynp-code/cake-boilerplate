<?php
declare(strict_types=1);

namespace App\Service\Kintone;

use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Text;
use RuntimeException;
use Throwable;

/**
 * サンプル kintone アプリ Webhook プロセッサ
 *
 * このクラスは boilerplate のサンプル実装。
 * 実際のアプリに合わせてフィールドコードを変更してください。
 *
 * ## フィールドコード対応表
 * | kintone フィールドコード | DB カラム               |
 * |--------------------------|-------------------------|
 * | レコード番号             | kintone_record_number   |
 * | 文字列1行                | single_line_text        |
 * | 文字列複数行             | multi_line_text         |
 * | チェックボックス         | checkbox_value (JSON)   |
 * | ドロップダウン           | dropdown_value          |
 * | ラジオボタン             | radio_button_value      |
 * | 添付ファイル             | attachments (JSON)      |
 * | 作成日時                 | kintone_created_at      |
 * | 作成者                   | kintone_creator         |
 * | 更新日時                 | kintone_updated_at      |
 * | 更新者                   | kintone_modifier        |
 */
class SampleKintoneWebhookProcessor extends AbstractKintoneWebhookProcessor
{
    use LocatorAwareTrait;

    /**
     * @param int $kintoneAppId kintone アプリID
     */
    public function __construct(private readonly int $kintoneAppId)
    {
    }

    /**
     * @return int
     */
    protected function appId(): int
    {
        return $this->kintoneAppId;
    }

    /**
     * kintone レコードを kintone_webhook_records テーブルに upsert する。
     *
     * @param array<string, mixed> $record kintone API レスポンスの record オブジェクト
     * @return void
     */
    protected function upsert(array $record): void
    {
        $recordNumber = (string)$this->value($record, 'レコード番号');

        if ($recordNumber === '') {
            throw new RuntimeException('レコード番号が取得できませんでした。');
        }

        /** @var \App\Model\Table\KintoneWebhookRecordsTable $table */
        $table = $this->fetchTable('KintoneWebhookRecords');

        // kintone_record_number で既存レコードを検索 (upsert キー)
        /** @var \App\Model\Entity\KintoneWebhookRecord|null $existing */
        $existing = $table->find()
            ->where(['kintone_record_number' => $recordNumber])
            ->first();

        $data = $this->mapFields($record);

        if ($existing !== null) {
            $entity = $table->patchEntity($existing, $data);
        } else {
            $entity = $table->newEntity(array_merge(['id' => Text::uuid()], $data));
        }

        $table->saveOrFail($entity);
    }

    /**
     * DELETE イベント: kintone_webhook_records から該当レコードを削除する。
     *
     * kintone の $id（レコードID）と レコード番号（RECORD_NUMBER フィールド）は
     * 通常同じ値になるため、$recordId を kintone_record_number の検索キーとして使用する。
     *
     * @param int $appId kintone アプリID
     * @param int $recordId kintone レコードID（レコード番号と同値を前提）
     * @return void
     */
    protected function handleDelete(int $appId, int $recordId): void
    {
        /** @var \App\Model\Table\KintoneWebhookRecordsTable $table */
        $table = $this->fetchTable('KintoneWebhookRecords');

        $table->deleteAll(['kintone_record_number' => (string)$recordId]);
    }

    // =========================================================================
    // private
    // =========================================================================

    /**
     * kintone レコード配列を DB 保存用配列にマッピングする。
     *
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function mapFields(array $record): array
    {
        return [
            'kintone_record_number' => (string)$this->value($record, 'レコード番号'),
            'single_line_text' => (string)$this->value($record, '文字列1行', ''),
            'multi_line_text' => (string)$this->value($record, '文字列複数行', ''),
            'checkbox_value' => json_encode(
                $this->checkboxValues($record, 'チェックボックス'),
                JSON_UNESCAPED_UNICODE,
            ),
            'dropdown_value' => (string)$this->value($record, 'ドロップダウン', ''),
            'radio_button_value' => (string)$this->value($record, 'ラジオボタン', ''),
            'attachments' => json_encode(
                $this->attachmentValues($record, '添付ファイル'),
                JSON_UNESCAPED_UNICODE,
            ),
            'kintone_created_at' => $this->parseDateTime($this->value($record, '作成日時')),
            'kintone_creator' => $this->userDisplayName($record, '作成者'),
            'kintone_updated_at' => $this->parseDateTime($this->value($record, '更新日時')),
            'kintone_modifier' => $this->userDisplayName($record, '更新者'),
        ];
    }

    /**
     * kintone の日時文字列を DateTime に変換する。
     *
     * @param mixed $value
     * @return \Cake\I18n\DateTime|null
     */
    private function parseDateTime(mixed $value): ?DateTime
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            return new DateTime($value);
        } catch (Throwable) {
            return null;
        }
    }
}
