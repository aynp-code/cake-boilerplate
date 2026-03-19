<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RolePermission $rolePermission
 */
?>

<?php
$this->assign('title', __('Role Permission'));
$this->Breadcrumbs->addMany([
    ['title' => __('Home'), 'url' => '/'],
    ['title' => __('List Role Permissions'), 'url' => ['action' => 'index']],
    ['title' => __('View')],
]);
?>



<div class="view card card-primary card-outline">
    <div class="card-header d-sm-flex">
        <h2 class="card-title"><?= h($rolePermission->controller) ?></h2>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">


                            <tr>
                <th><?= __('Role') ?></th>
                <td><?= $rolePermission->has('role')
                    ? $this->Html->link(
                        $rolePermission->role->display_name,
                        ['controller' => 'Roles', 'action' => 'view', $rolePermission->role->id]
                    )
                    : '' ?></td>
            </tr>

        

                    <tr>
                <th><?= __('Plugin') ?></th>
                <td><?= h($rolePermission->plugin) ?></td>
            </tr>
    

                    <tr>
                <th><?= __('Prefix') ?></th>
                <td><?= h($rolePermission->prefix) ?></td>
            </tr>
    

                    <tr>
                <th><?= __('Controller') ?></th>
                <td><?= h($rolePermission->controller) ?></td>
            </tr>
    

                    <tr>
                <th><?= __('Action') ?></th>
                <td><?= h($rolePermission->action) ?></td>
            </tr>
    

                    <tr>
                <th><?= __('Allowed') ?></th>
                <td><?= $rolePermission->allowed ? __('Yes') : __('No'); ?></td>
            </tr>

        

                    <tr>
                <th><?= __('Created') ?></th>
                <td><?= h($rolePermission->created) ?></td>
            </tr>

        

                    <tr>
                <th><?= __('Created By') ?></th>
                <td>
                    <?php if ($rolePermission->created_by_user !== null) : ?>
                        <?= $this->Html->link(
                            h($rolePermission->created_by_user->display_name),
                            ['controller' => 'Users', 'action' => 'view', $rolePermission->created_by_user->id]
                        ) ?>
                    <?php else : ?>
                        <?= h($rolePermission->created_by) ?>
                    <?php endif; ?>
                </td>
            </tr>

    

                    <tr>
                <th><?= __('Modified') ?></th>
                <td><?= h($rolePermission->modified) ?></td>
            </tr>

        

                    <tr>
                <th><?= __('Modified By') ?></th>
                <td>
                    <?php if ($rolePermission->modified_by_user !== null) : ?>
                        <?= $this->Html->link(
                            h($rolePermission->modified_by_user->display_name),
                            ['controller' => 'Users', 'action' => 'view', $rolePermission->modified_by_user->id]
                        ) ?>
                    <?php else : ?>
                        <?= h($rolePermission->modified_by) ?>
                    <?php endif; ?>
                </td>
            </tr>

        


        </table>
    </div>

    <div class="card-footer d-flex">
        <div class="mr-auto">
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $rolePermission->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $rolePermission->id), 'class' => 'btn btn-danger']
            ) ?>
        </div>
        <div class="ml-auto">
            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $rolePermission->id], ['class' => 'btn btn-secondary']) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>
</div>

