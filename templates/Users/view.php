<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var string|null $currentUserId
 */
?>

<?php
$this->assign('title', __('User'));
$this->Breadcrumbs->addMany([
    ['title' => __('Home'), 'url' => '/'],
    ['title' => __('List Users'), 'url' => ['action' => 'index']],
    ['title' => __('View')],
]);
?>



<div class="view card card-primary card-outline">
    <div class="card-header d-sm-flex">
        <h2 class="card-title"><?= h($user->display_name) ?></h2>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">


                    <tr>
                <th><?= __('Username') ?></th>
                <td><?= h($user->username) ?></td>
            </tr>
    

                    <tr>
                <th><?= __('Display Name') ?></th>
                <td><?= h($user->display_name) ?></td>
            </tr>
    

                    <tr>
                <th><?= __('Email') ?></th>
                <td><?= h($user->email) ?></td>
            </tr>
    

                    <tr>
                <th><?= __('Kintone Username') ?></th>
                <td><?= h($user->kintone_username) ?></td>
            </tr>
    

                    <tr>
                <th><?= __('Kintone Linked') ?></th>
                <td>
                    <?php if ($user->is_kintone_linked) : ?>
                        <span class="badge badge-success mr-2"><i class="fas fa-check-circle mr-1"></i><?= __('Linked') ?></span>
                        <?php if ($currentUserId === $user->id) : ?>
                            <?= $this->Form->postLink(
                                '<i class="fas fa-unlink mr-1"></i>' . __('Revoke'),
                                ['controller' => 'Cybozu', 'action' => 'revoke'],
                                [
                                    'class'   => 'btn btn-sm btn-outline-danger',
                                    'escape'  => false,
                                    'confirm' => __('Revoke Kintone link? You will need to re-authenticate.'),
                                ]
                            ) ?>
                        <?php endif; ?>
                    <?php elseif (!empty($user->kintone_username)) : ?>
                        <span class="badge badge-warning mr-2"><i class="fas fa-unlink mr-1"></i><?= __('Not linked') ?></span>
                        <?php if ($currentUserId === $user->id) : ?>
                            <?= $this->Html->link(
                                '<i class="fas fa-link mr-1"></i>' . __('Connect Kintone'),
                                ['controller' => 'Cybozu', 'action' => 'connect'],
                                ['class' => 'btn btn-sm btn-primary', 'escape' => false]
                            ) ?>
                        <?php endif; ?>
                    <?php else : ?>
                        <span class="badge badge-secondary"><?= __('No kintone_username set') ?></span>
                    <?php endif; ?>
                </td>
            </tr>
    

                            <tr>
                <th><?= __('Role') ?></th>
                <td><?= $user->has('role')
                    ? $this->Html->link(
                        $user->role->display_name,
                        ['controller' => 'Roles', 'action' => 'view', $user->role->id]
                    )
                    : '' ?></td>
            </tr>

        

                    <tr>
                <th><?= __('Is Active') ?></th>
                <td><?= $user->is_active ? __('Yes') : __('No'); ?></td>
            </tr>

        

                    <tr>
                <th><?= __('Created') ?></th>
                <td><?= h($user->created) ?></td>
            </tr>

        

                    <tr>
                <th><?= __('Created By') ?></th>
                <td>
                    <?php if ($user->created_by_user !== null) : ?>
                        <?= $this->Html->link(
                            h($user->created_by_user->display_name),
                            ['controller' => 'Users', 'action' => 'view', $user->created_by_user->id]
                        ) ?>
                    <?php else : ?>
                        <?= h($user->created_by) ?>
                    <?php endif; ?>
                </td>
            </tr>

    

                    <tr>
                <th><?= __('Modified') ?></th>
                <td><?= h($user->modified) ?></td>
            </tr>

        

                    <tr>
                <th><?= __('Modified By') ?></th>
                <td>
                    <?php if ($user->modified_by_user !== null) : ?>
                        <?= $this->Html->link(
                            h($user->modified_by_user->display_name),
                            ['controller' => 'Users', 'action' => 'view', $user->modified_by_user->id]
                        ) ?>
                    <?php else : ?>
                        <?= h($user->modified_by) ?>
                    <?php endif; ?>
                </td>
            </tr>

        


        </table>
    </div>

    <div class="card-footer d-flex">
        <div class="mr-auto">
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $user->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $user->id), 'class' => 'btn btn-danger']
            ) ?>
        </div>
        <div class="ml-auto">
            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $user->id], ['class' => 'btn btn-secondary']) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>
</div>