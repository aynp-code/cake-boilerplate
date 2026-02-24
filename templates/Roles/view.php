<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Role $role
 */
?>

<?php
$this->assign('title', __('Role'));
$this->Breadcrumbs->addMany([
    ['title' => __('Home'), 'url' => '/'],
    ['title' => __('List Roles'), 'url' => ['action' => 'index']],
    ['title' => __('View')],
]);
?>



<div class="view card card-primary card-outline">
    <div class="card-header d-sm-flex">
        <h2 class="card-title"><?= h($role->display_name) ?></h2>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">


                    <tr>
                <th><?= __('Display Name') ?></th>
                <td><?= h($role->display_name) ?></td>
            </tr>
    

                    <tr>
                <th><?= __('Description') ?></th>
                <td><?= h($role->description) ?></td>
            </tr>
    

                    <tr>
                <th><?= __('Created') ?></th>
                <td><?= h($role->created) ?></td>
            </tr>

        

                    <tr>
                <th><?= __('Created By') ?></th>
                <td>
                    <?php if ($role->has('created_by_user')) : ?>
                        <?= $this->Html->link(
                            h($role->created_by_user->display_name),
                            ['controller' => 'Users', 'action' => 'view', $role->created_by_user->id]
                        ) ?>
                    <?php else : ?>
                        <?= h($role->created_by) ?>
                    <?php endif; ?>
                </td>
            </tr>

    

                    <tr>
                <th><?= __('Modified') ?></th>
                <td><?= h($role->modified) ?></td>
            </tr>

        

                    <tr>
                <th><?= __('Modified By') ?></th>
                <td>
                    <?php if ($role->has('modified_by_user')) : ?>
                        <?= $this->Html->link(
                            h($role->modified_by_user->display_name),
                            ['controller' => 'Users', 'action' => 'view', $role->modified_by_user->id]
                        ) ?>
                    <?php else : ?>
                        <?= h($role->modified_by) ?>
                    <?php endif; ?>
                </td>
            </tr>

        


        </table>
    </div>

    <div class="card-footer d-flex">
        <div class="mr-auto">
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $role->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $role->id), 'class' => 'btn btn-danger']
            ) ?>
        </div>
        <div class="ml-auto">
            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $role->id], ['class' => 'btn btn-secondary']) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>
</div>



<div class="related related-rolePermission view card">
    <div class="card-header d-flex">
        <h3 class="card-title"><?= __('Related Role Permissions') ?></h3>
        <div class="ml-auto">
            <?= $this->Html->link(__('New Role Permission'), ['controller' => 'RolePermissions', 'action' => 'add', '?' => ['role_id' => $role->id]], ['class' => 'btn btn-primary btn-sm']) ?>
            <?= $this->Html->link(__('List Role Permissions'), ['controller' => 'RolePermissions', 'action' => 'index'], ['class' => 'btn btn-primary btn-sm']) ?>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <tr>
                                <th class="actions"><?= __('Actions') ?></th>

                                <th><?= __('Plugin') ?></th>
                                                <th><?= __('Prefix') ?></th>
                                                <th><?= __('Controller') ?></th>
                                                <th><?= __('Action') ?></th>
                                                <th><?= __('Allowed') ?></th>
                                                <th><?= __('Created') ?></th>
                                                <th><?= __('Created By User') ?></th>
                                                <th><?= __('Modified') ?></th>
                                                <th><?= __('Modified By User') ?></th>
                            </tr>

            <?php if (empty($role->role_permissions)) : ?>
                <tr>
                    <td colspan="10" class="text-muted">
                        <?= __('Role Permissions record not found!') ?>
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ($role->role_permissions as $rolePermission) : ?>
                    <tr>

                                                <td class="actions text-nowrap">
                            <?= $this->Html->link(
                                '<i class="fas fa-eye"></i>',
                                ['controller' => 'RolePermissions', 'action' => 'view', $rolePermission->id],
                                [
                                    'class' => 'btn btn-xs btn-outline-info',
                                    'escape' => false,
                                    'title' => __('View'),
                                    'data-toggle' => 'tooltip',
                                ]
                            ) ?>

                            <?= $this->Html->link(
                                '<i class="fas fa-edit"></i>',
                                ['controller' => 'RolePermissions', 'action' => 'edit', $rolePermission->id],
                                [
                                    'class' => 'btn btn-xs btn-outline-primary ml-1',
                                    'escape' => false,
                                    'title' => __('Edit'),
                                    'data-toggle' => 'tooltip',
                                ]
                            ) ?>

                            <?= $this->Form->postLink(
                                '<i class="fas fa-trash"></i>',
                                ['controller' => 'RolePermissions', 'action' => 'delete', $rolePermission->id],
                                [
                                    'class' => 'btn btn-xs btn-outline-danger ml-1',
                                    'escape' => false,
                                    'title' => __('Delete'),
                                    'data-toggle' => 'tooltip',
                                    'confirm' => __('Are you sure you want to delete # {0}?', $rolePermission->id),
                                ]
                            ) ?>
                        </td>

                                                <td><?= h($rolePermission->plugin) ?></td>
                                                                        <td><?= h($rolePermission->prefix) ?></td>
                                                                        <td><?= h($rolePermission->controller) ?></td>
                                                                        <td><?= h($rolePermission->action) ?></td>
                                                                        <td><?= h($rolePermission->allowed) ?></td>
                                                                        <td><?= h($rolePermission->created) ?></td>
                                                                        <td>
                            <?php if ($rolePermission->has('created_by_user')) : ?>
                                <?= $this->Html->link(
                                    h($rolePermission->created_by_user->display_name),
                                    ['controller' => 'Users', 'action' => 'view', $rolePermission->created_by_user->id]
                                ) ?>
                            <?php else : ?>
                                <?= h($rolePermission->created_by) ?>
                            <?php endif; ?>
                        </td>
                                                                        <td><?= h($rolePermission->modified) ?></td>
                                                                        <td>
                            <?php if ($rolePermission->has('modified_by_user')) : ?>
                                <?= $this->Html->link(
                                    h($rolePermission->modified_by_user->display_name),
                                    ['controller' => 'Users', 'action' => 'view', $rolePermission->modified_by_user->id]
                                ) ?>
                            <?php else : ?>
                                <?= h($rolePermission->modified_by) ?>
                            <?php endif; ?>
                        </td>
                                            </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</div>


<div class="related related-user view card">
    <div class="card-header d-flex">
        <h3 class="card-title"><?= __('Related Users') ?></h3>
        <div class="ml-auto">
            <?= $this->Html->link(__('New User'), ['controller' => 'Users', 'action' => 'add', '?' => ['role_id' => $role->id]], ['class' => 'btn btn-primary btn-sm']) ?>
            <?= $this->Html->link(__('List Users'), ['controller' => 'Users', 'action' => 'index'], ['class' => 'btn btn-primary btn-sm']) ?>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <tr>
                                <th class="actions"><?= __('Actions') ?></th>

                                <th><?= __('Username') ?></th>
                                                <th><?= __('Display Name') ?></th>
                                                <th><?= __('Email') ?></th>
                                                <th><?= __('Is Active') ?></th>
                                                <th><?= __('Created') ?></th>
                                                <th><?= __('Created By User') ?></th>
                                                <th><?= __('Modified') ?></th>
                                                <th><?= __('Modified By User') ?></th>
                            </tr>

            <?php if (empty($role->users)) : ?>
                <tr>
                    <td colspan="9" class="text-muted">
                        <?= __('Users record not found!') ?>
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ($role->users as $user) : ?>
                    <tr>

                                                <td class="actions text-nowrap">
                            <?= $this->Html->link(
                                '<i class="fas fa-eye"></i>',
                                ['controller' => 'Users', 'action' => 'view', $user->id],
                                [
                                    'class' => 'btn btn-xs btn-outline-info',
                                    'escape' => false,
                                    'title' => __('View'),
                                    'data-toggle' => 'tooltip',
                                ]
                            ) ?>

                            <?= $this->Html->link(
                                '<i class="fas fa-edit"></i>',
                                ['controller' => 'Users', 'action' => 'edit', $user->id],
                                [
                                    'class' => 'btn btn-xs btn-outline-primary ml-1',
                                    'escape' => false,
                                    'title' => __('Edit'),
                                    'data-toggle' => 'tooltip',
                                ]
                            ) ?>

                            <?= $this->Form->postLink(
                                '<i class="fas fa-trash"></i>',
                                ['controller' => 'Users', 'action' => 'delete', $user->id],
                                [
                                    'class' => 'btn btn-xs btn-outline-danger ml-1',
                                    'escape' => false,
                                    'title' => __('Delete'),
                                    'data-toggle' => 'tooltip',
                                    'confirm' => __('Are you sure you want to delete # {0}?', $user->id),
                                ]
                            ) ?>
                        </td>

                                                <td><?= h($user->username) ?></td>
                                                                        <td><?= h($user->display_name) ?></td>
                                                                        <td><?= h($user->email) ?></td>
                                                                        <td><?= h($user->is_active) ?></td>
                                                                        <td><?= h($user->created) ?></td>
                                                                        <td>
                            <?php if ($user->has('created_by_user')) : ?>
                                <?= $this->Html->link(
                                    h($user->created_by_user->display_name),
                                    ['controller' => 'Users', 'action' => 'view', $user->created_by_user->id]
                                ) ?>
                            <?php else : ?>
                                <?= h($user->created_by) ?>
                            <?php endif; ?>
                        </td>
                                                                        <td><?= h($user->modified) ?></td>
                                                                        <td>
                            <?php if ($user->has('modified_by_user')) : ?>
                                <?= $this->Html->link(
                                    h($user->modified_by_user->display_name),
                                    ['controller' => 'Users', 'action' => 'view', $user->modified_by_user->id]
                                ) ?>
                            <?php else : ?>
                                <?= h($user->modified_by) ?>
                            <?php endif; ?>
                        </td>
                                            </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</div>
