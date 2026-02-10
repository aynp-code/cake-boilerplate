<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Cache\Cache;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Inflector;

class RolePermissionChecker
{
    use LocatorAwareTrait;

    private const CACHE_CONFIG = '_cake_permissions';
    private const CACHE_KEY_PREFIX = 'perm:role:';

    /** @var array<string, array<string,bool>> roleId => permissionMap */
    private array $memo = [];

    /**
     * @param string $roleId
     * @param array{plugin?:?string,prefix?:mixed,controller:string,action:string} $target
     */
    public function can(string $roleId, array $target): bool
    {
        $map = $this->getRolePermissionMap($roleId);

        $plugin = $this->normalizeNullableString($target['plugin'] ?? null) ?? '';
        $prefix = $this->normalizePrefix($target['prefix'] ?? null) ?? '';
        $controller = (string)$target['controller'];
        $action = (string)$target['action'];

        // 具体→汎用（ワイルドカード）
        $candidates = [
            [$controller, $action],
            ['*', $action],
            [$controller, '*'],
            ['*', '*'],
        ];

        foreach ($candidates as [$c, $a]) {
            $k = $this->key($plugin, $prefix, (string)$c, (string)$a);
            if (isset($map[$k])) {
                return true; // map には true しか入れない設計
            }
        }

        return false;
    }

    /**
     * Redis(=Cake Cache) から role の許可マップを取る。
     * 無ければDBから作って保存。
     *
     * ※ map は「allowed=true だけを保持」する。存在しない=拒否。
     *
     * @return array<string, bool>
     */
    public function getRolePermissionMap(string $roleId): array
    {
        // 1リクエスト内メモ（同じroleIdで何度も Cache を叩かない）
        if (isset($this->memo[$roleId])) {
            return $this->memo[$roleId];
        }

        $cacheKey = self::CACHE_KEY_PREFIX . $roleId;

        /** @var array<string,bool>|false $cached */
        $cached = Cache::read($cacheKey, self::CACHE_CONFIG);
        if (is_array($cached)) {
            return $this->memo[$roleId] = $cached;
        }

        // DBから構築（DB側も正規化済み文字列を持つ前提）
        $RolePermissions = $this->fetchTable('RolePermissions');
        $rows = $RolePermissions->find()
            ->select(['plugin', 'prefix', 'controller', 'action'])
            ->where(['role_id' => $roleId, 'allowed' => true])
            ->all();

        $map = [];
        foreach ($rows as $r) {
            $plugin = $this->normalizeNullableString($r->plugin ?? null) ?? '';
            $prefix = $this->normalizePrefix($r->prefix ?? null) ?? '';
            $controller = (string)$r->controller;
            $action = (string)$r->action;

            $map[$this->key($plugin, $prefix, $controller, $action)] = true;
        }

        // cache write が失敗しても認可自体は動かす（落とさない）
        Cache::write($cacheKey, $map, self::CACHE_CONFIG);

        return $this->memo[$roleId] = $map;
    }

    public function invalidateRole(string $roleId): void
    {
        unset($this->memo[$roleId]);
        Cache::delete(self::CACHE_KEY_PREFIX . $roleId, self::CACHE_CONFIG);
    }

    private function key(string $plugin, string $prefix, string $controller, string $action): string
    {
        return "{$plugin}|{$prefix}|{$controller}|{$action}";
    }

    private function normalizeNullableString(mixed $v): ?string
    {
        if (!is_string($v)) {
            return null;
        }
        $v = trim($v);
        return $v === '' ? null : $v;
    }

    /**
     * prefix の表現ゆれを吸収して、DB/Catalog と同じ文字列に寄せる。
     *
     * - null/'' => null
     * - array => "Admin/Api" のように連結
     * - snake_case 等 => CamelCase
     *
     * ※ Acl.prefixAliases のような「別名変換」は採用しない
     *   （role名 "01.admin" と prefix "Admin" は無関係、誤変換の原因になるため）
     */
    private function normalizePrefix(mixed $prefix): ?string
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

        // "admin_panel/inner" などを "AdminPanel/Inner" に寄せる
        $parts = array_filter(explode('/', $prefix), fn($p) => $p !== '');
        $parts = array_map(fn($p) => Inflector::camelize($p), $parts);
        $normalized = implode('/', $parts);

        return $normalized === '' ? null : $normalized;
    }
}
