<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * KintoneWebhookRecord Entity
 *
 * kintone webhook 経由で同期されたレコード。
 * kintone_record_number を upsert キーとして使用する。
 *
 * @property string $id
 * @property string $kintone_record_number   kintone レコード番号 (upsert キー)
 * @property string|null $single_line_text   文字列1行
 * @property string|null $multi_line_text    文字列複数行
 * @property array<int, string> $checkbox_value    チェックボックス (JSON配列)
 * @property string|null $dropdown_value     ドロップダウン
 * @property string|null $radio_button_value ラジオボタン
 * @property array<int, array<string, string>> $attachments  添付ファイル (JSON配列)
 * @property \Cake\I18n\DateTime|null $kintone_created_at  kintone 作成日時
 * @property string|null $kintone_creator    kintone 作成者
 * @property \Cake\I18n\DateTime|null $kintone_updated_at  kintone 更新日時
 * @property string|null $kintone_modifier   kintone 更新者
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class KintoneWebhookRecord extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'kintone_record_number' => true,
        'single_line_text' => true,
        'multi_line_text' => true,
        'checkbox_value' => true,
        'dropdown_value' => true,
        'radio_button_value' => true,
        'attachments' => true,
        'kintone_created_at' => true,
        'kintone_creator' => true,
        'kintone_updated_at' => true,
        'kintone_modifier' => true,
        'created' => true,
        'modified' => true,
    ];
}
