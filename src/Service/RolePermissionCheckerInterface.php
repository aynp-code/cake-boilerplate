<?php
declare(strict_types=1);

namespace App\Service;

interface RolePermissionCheckerInterface
{
    /**
     * 指定ロールが対象アクションを実行できるか判定する。
     *
     * @param array<string, mixed> $target
     */
    public function can(string $roleId, array $target): bool;

    /**
     * 指定ロールの権限キャッシュを無効化する。
     *
     * ロールの権限が変更された際に呼び出す。
     * テストや将来の実装でもこのメソッドをモック可能にするためインターフェースに含める。
     */
    public function invalidateRole(string $roleId): void;
}
