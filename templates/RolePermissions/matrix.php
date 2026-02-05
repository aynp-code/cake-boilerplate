<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $roles
 * @var array<int,array{plugin:mixed,prefix:mixed,controller:string,action:string}> $actionRows
 * @var array<string,array<string,bool>> $allowedMap
 */
?>

<h1><?= __('Role Permissions Matrix') ?></h1>

<?= $this->Form->create() ?>

<table class="table">
    <thead>
        <tr>
            <th><?= __('Controller / Action') ?></th>
            <?php foreach ($roles as $role): ?>
                <th><?= h($role->display_name ?? $role->id) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($actionRows as $row): ?>
            <?php
                // ★ Catalog由来なので配列アクセス
                $plugin = $row['plugin'] ?? null;
                $prefix = $row['prefix'] ?? null;
                $controller = $row['controller'];
                $action = $row['action'];

                // フォーム送信用 rowKey（Controller側の _rowKey() と同じ規則）
                $rowKey = implode('||', [
                    $plugin ?? '',
                    $prefix ?? '',
                    $controller,
                    $action,
                ]);

                // allowedMap 用のキー（Controller側と同じ json_encode 方式）
                $mapKey = json_encode([$plugin, $prefix, $controller, $action], JSON_UNESCAPED_UNICODE);
            ?>
            <tr>
                <td>
                    <?= h($controller) ?>::<?= h($action) ?>
                    <?php if ($prefix): ?>
                        <small style="opacity:.7;">(<?= h($prefix) ?>)</small>
                    <?php endif; ?>
                </td>

                <?php foreach ($roles as $role): ?>
                    <?php
                        $roleId = (string)$role->id;
                        $checked = !empty($allowedMap[$mapKey][$roleId]);
                    ?>
                    <td style="text-align:center;">
                        <input
                            type="checkbox"
                            name="perm[<?= h($rowKey) ?>][<?= h($roleId) ?>]"
                            value="1"
                            <?= $checked ? 'checked' : '' ?>
                        >
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?= $this->Form->button(__('Save')) ?>
<?= $this->Form->end() ?>
