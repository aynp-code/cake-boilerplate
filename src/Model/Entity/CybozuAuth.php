<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\Utility\Security;

/**
 * CybozuAuth Entity
 *
 * access_token / refresh_token は Security::encrypt() で暗号化して保存します。
 * getter/setter を通すことで、コード上は常に平文で扱えます。
 *
 * @property string $id
 * @property string $user_id
 * @property string $access_token   （平文で読み書き可、DB は暗号文）
 * @property string $refresh_token  （平文で読み書き可、DB は暗号文）
 * @property \Cake\I18n\DateTime $expires_at
 * @property string|null $scope
 * @property \Cake\I18n\DateTime $created
 * @property string $created_by
 * @property \Cake\I18n\DateTime $modified
 * @property string $modified_by
 *
 * @property \App\Model\Entity\User $user
 */
class CybozuAuth extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id'       => true,
        'access_token'  => true,
        'refresh_token' => true,
        'expires_at'    => true,
        'scope'         => true,
        'created'       => true,
        'created_by'    => true,
        'modified'      => true,
        'modified_by'   => true,
        'user'          => true,
    ];

    /**
     * 読み取り時に復号して返す
     */
    protected function _getAccessToken(string $value): string
    {
        return $this->decrypt($value);
    }

    /**
     * 書き込み時に暗号化して保存
     */
    protected function _setAccessToken(string $value): string
    {
        return $this->encrypt($value);
    }

    /**
     * 読み取り時に復号して返す
     */
    protected function _getRefreshToken(string $value): string
    {
        return $this->decrypt($value);
    }

    /**
     * 書き込み時に暗号化して保存
     */
    protected function _setRefreshToken(string $value): string
    {
        return $this->encrypt($value);
    }

    /**
     * アクセストークンが有効期限内かどうか（バッファ 60 秒）
     */
    public function isAccessTokenValid(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }
        return $this->expires_at->isFuture()
            && $this->expires_at->diffInSeconds() > 60;
    }

    // -----------------------------------------------------------------------
    // private helpers
    // -----------------------------------------------------------------------

    private function encrypt(string $plain): string
    {
        // 空文字はそのまま（暗号化済みかどうかの判定用）
        if ($plain === '') {
            return '';
        }
        // すでに暗号化済みなら二重暗号化しない（DB から読んで再セットする場合）
        if ($this->looksEncrypted($plain)) {
            return $plain;
        }
        return base64_encode(Security::encrypt($plain, Security::getSalt()));
    }

    private function decrypt(string $cipher): string
    {
        if ($cipher === '') {
            return '';
        }
        try {
            $decoded = base64_decode($cipher, strict: true);
            if ($decoded === false) {
                return $cipher; // すでに平文の場合（テスト等）
            }
            $plain = Security::decrypt($decoded, Security::getSalt());
            return $plain ?? $cipher;
        } catch (\Throwable) {
            return $cipher;
        }
    }

    /**
     * base64 + Security::encrypt() の出力らしいかどうかを簡易判定
     */
    private function looksEncrypted(string $value): bool
    {
        $decoded = base64_decode($value, strict: true);
        if ($decoded === false) {
            return false;
        }
        // Security::encrypt の出力は最低でも HMAC(32byte) + IV(16byte) = 48byte 以上
        return strlen($decoded) >= 48;
    }
}
