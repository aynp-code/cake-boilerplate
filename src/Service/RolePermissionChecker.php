<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Cache\Cache;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;

class RolePermissionChecker implements RolePermissionCheckerInterface
{
    use LocatorAwareTrait;

    private const CACHE_CONFIG = '_cake_permissions';
    private const CACHE_KEY_PREFIX = 'perm:role:';

    /**
     * @var array<string, array<string,bool>> roleId => permissionMap
     */
    private array $memo = [];

    /**
     * normalizePrefix / normalizePlugin の重複実装を RoutePermissionTargetNormalizer に委譲。
     */
    public function __construct(
        private readonly RoutePermissionTargetNormalizer $normalizer = new RoutePermissionTargetNormalizer(),
    ) {
    }

    /**
     * Check if a role is allowed to access a target controller/action.
     *
     * @param string $roleId The role ID to check.
     * @param array<string, mixed> $target The target to check.
     * @return bool
     */
    public function can(string $roleId, array $target): bool
    {
        $map = $this->getRolePermissionMap($roleId);

        $plugin = $this->normalizer->normalizePlugin($target['plugin'] ?? null) ?? '';
        $prefix = $this->normalizer->normalizePrefix($target['prefix'] ?? null) ?? '';
        $controller = (string)$target['controller'];
        $action = (string)$target['action'];

        // 具体 → 汎用（ワイルドカード）の順にチェック
        $candidates = [
            [$controller, $action],
            ['*', $action],
            [$controller, '*'],
            ['*', '*'],
        ];

        foreach ($candidates as [$c, $a]) {
            $k = $this->key($plugin, $prefix, (string)$c, (string)$a);
            if (isset($map[$k])) {
                return true;
            }
        }

        return false;
    }

    /**
     * role の許可マップをキャッシュ or DB から取得する。
     * map には allowed=true のエントリのみ保持（存在しない = 拒否）。
     *
     * @return array<string, bool>
     */
    public function getRolePermissionMap(string $roleId): array
    {
        if (isset($this->memo[$roleId])) {
            return $this->memo[$roleId];
        }

        $cacheKey = self::CACHE_KEY_PREFIX . $roleId;

        /** @var array<string,bool>|false $cached */
        $cached = Cache::read($cacheKey, self::CACHE_CONFIG);
        if (is_array($cached)) {
            return $this->memo[$roleId] = $cached;
        }

        $RolePermissions = $this->fetchTable('RolePermissions');
        $rows = $RolePermissions->find()
            ->select(['plugin', 'prefix', 'controller', 'action'])
            ->where(['role_id' => $roleId, 'allowed' => true])
            ->all();

        $map = [];
        foreach ($rows as $r) {
            if (!($r instanceof EntityInterface)) {
                continue;
            }

            $plugin = $this->normalizer->normalizePlugin($r->get('plugin')) ?? '';
            $prefix = $this->normalizer->normalizePrefix($r->get('prefix')) ?? '';
            $controller = (string)$r->get('controller');
            $action = (string)$r->get('action');

            $map[$this->key($plugin, $prefix, $controller, $action)] = true;
        }

        Cache::write($cacheKey, $map, self::CACHE_CONFIG);

        return $this->memo[$roleId] = $map;
    }

    /**
     * Invalidate cached permissions for a role.
     *
     * @param string $roleId The role ID to invalidate.
     * @return void
     */
    public function invalidateRole(string $roleId): void
    {
        unset($this->memo[$roleId]);
        Cache::delete(self::CACHE_KEY_PREFIX . $roleId, self::CACHE_CONFIG);
    }

    /**
     * Build a cache key for a permission entry.
     *
     * @param string $plugin The plugin name.
     * @param string $prefix The prefix.
     * @param string $controller The controller name.
     * @param string $action The action name.
     * @return string
     */
    private function key(string $plugin, string $prefix, string $controller, string $action): string
    {
        return "{$plugin}|{$prefix}|{$controller}|{$action}";
    }
}
