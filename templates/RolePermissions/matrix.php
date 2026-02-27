<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $roles
 * @var array<int,array{plugin:mixed,prefix:mixed,controller:string,action:string}> $actionRows
 * @var array<string,array<string,bool>> $allowedMap
 */
$this->assign('title', __('Role Permissions Matrix'));
$this->Breadcrumbs->addMany([
    ['title' => __('Home'), 'url' => '/'],
    ['title' => __('Role Permissions Matrix')],
]);
?>

<div class="card card-primary card-outline">
    <?= $this->Form->create() ?>

    <div class="card-header d-flex flex-column flex-md-row">
        <h2 class="card-title"><!-- --></h2>
        <div class="d-flex ml-auto">
            <?= $this->Html->tag('button', __('Save'), [
                'type'   => 'submit',
                'class'  => 'btn btn-primary btn-sm',
                'escape' => false,
            ]) ?>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th><?= __('Controller / Action') ?></th>
                    <?php foreach ($roles as $role): ?>
                        <th class="text-center"><?= h($role->display_name ?? $role->id) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($actionRows as $row): ?>
                    <?php
                        $plugin     = $row['plugin'] ?? null;
                        $prefix     = $row['prefix'] ?? null;
                        $controller = $row['controller'];
                        $action     = $row['action'];

                        $rowKey = implode('||', [
                            $plugin ?? '',
                            $prefix ?? '',
                            $controller,
                            $action,
                        ]);

                        $mapKey = json_encode([$plugin, $prefix, $controller, $action], JSON_UNESCAPED_UNICODE);
                    ?>
                    <tr>
                        <td>
                            <?= h($controller) ?>::<?= h($action) ?>
                            <?php if ($prefix): ?>
                                <small class="text-muted ml-1">(<?= h($prefix) ?>)</small>
                            <?php endif; ?>
                        </td>
                        <?php foreach ($roles as $role): ?>
                            <?php
                                $roleId  = (string)$role->id;
                                $checked = !empty($allowedMap[$mapKey][$roleId]);
                            ?>
                            <td class="text-center">
                                <div class="custom-control custom-checkbox">
                                    <input
                                        type="checkbox"
                                        class="custom-control-input"
                                        id="perm_<?= h($rowKey) ?>_<?= h($roleId) ?>"
                                        name="perm[<?= h($rowKey) ?>][<?= h($roleId) ?>]"
                                        value="1"
                                        <?= $checked ? 'checked' : '' ?>
                                    >
                                    <label
                                        class="custom-control-label"
                                        for="perm_<?= h($rowKey) ?>_<?= h($roleId) ?>"
                                    ></label>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card-footer d-flex">
        <div class="ml-auto">
            <?= $this->Html->tag('button', __('Save'), [
                'type'   => 'submit',
                'class'  => 'btn btn-primary',
                'escape' => false,
            ]) ?>
        </div>
    </div>

    <?= $this->Form->end() ?>
</div>