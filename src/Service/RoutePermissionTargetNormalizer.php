<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Utility\Inflector;

/**
 * 権限制御用のターゲット（plugin/prefix/controller/action）を
 * “DBと照合できる形” に正規化する責務を1箇所に集約する。
 *
 * - prefix は string/array/null を受け、CamelCase + '/' 連結に統一
 * - plugin は '' を null に寄せる
 *
 * ※ Acl.prefixAliases のような「prefix別名変換」は採用しない
 *   （role名 "01.admin" と prefix "Admin" は無関係、誤変換の原因になるため）
 */
class RoutePermissionTargetNormalizer
{
    /**
     * Normalize the plugin name to a consistent format.
     *
     * @param mixed $plugin The plugin value to normalize.
     * @return string|null
     */
    public function normalizePlugin(mixed $plugin): ?string
    {
        return $this->normalizeNullableString($plugin);
    }

    /**
     * @param mixed $prefix string|array|null
     */
    public function normalizePrefix(mixed $prefix): ?string
    {
        if ($prefix === null) {
            return null;
        }

        // prefix が配列（ネストprefix）なら / で連結
        if (is_array($prefix)) {
            $prefix = implode('/', array_map('strval', $prefix));
        }

        $prefix = $this->normalizeNullableString($prefix);
        if ($prefix === null) {
            return null;
        }

        // 'admin_panel' や 'api/v1' を 'AdminPanel' / 'Api/V1' に統一
        $parts = array_filter(explode('/', $prefix), fn($p) => $p !== '');
        $parts = array_map(fn($p) => Inflector::camelize($p), $parts);
        $prefix = implode('/', $parts);

        return $prefix === '' ? null : $prefix;
    }

    /**
     * Normalize a mixed value to a nullable trimmed string.
     *
     * @param mixed $v The value to normalize.
     * @return string|null
     */
    private function normalizeNullableString(mixed $v): ?string
    {
        if (!is_string($v)) {
            return null;
        }
        $v = trim($v);

        return $v === '' ? null : $v;
    }
}
