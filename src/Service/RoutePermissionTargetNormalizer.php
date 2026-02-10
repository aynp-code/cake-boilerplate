<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Utility\Inflector;

/**
 * 権限制御用のターゲット（plugin/prefix/controller/action）を
 * “DBと照合できる形” に正規化する責務を1箇所に集約する。
 *
 * - prefix は string/array/null を受け、CamelCase + '/' 連結に統一
 * - prefixAliases があれば DB 表現に寄せる（例: Admin => 01.admin）
 * - plugin は '' を null に寄せる
 */
class RoutePermissionTargetNormalizer
{
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
        $prefix = $prefix === '' ? null : $prefix;

        // 別名（DB表現を固定したい場合）
        if ($prefix !== null) {
            $aliases = Configure::read('Acl.prefixAliases', []);
            if (is_array($aliases) && isset($aliases[$prefix]) && is_string($aliases[$prefix])) {
                $prefix = $aliases[$prefix];
            }
        }

        return $prefix;
    }

    private function normalizeNullableString(mixed $v): ?string
    {
        if (!is_string($v)) {
            return null;
        }
        $v = trim($v);
        return $v === '' ? null : $v;
    }
}
