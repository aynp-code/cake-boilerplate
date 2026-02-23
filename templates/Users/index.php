<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User[]|\Cake\Collection\CollectionInterface $users
 */
?>

<?php
$this->assign('title', __('Users'));
$this->Breadcrumbs->add([
    ['title' => __('Home'), 'url' => '/'],
    ['title' => __('List Users')],
]);
?>




<div class="card card-primary card-outline">
    <div class="card-header d-flex flex-column flex-md-row">
        <h2 class="card-title">
            <!-- -->
        </h2>
        <div class="d-flex ml-auto">
            <?= $this->Paginator->limitControl([], null, [
                'label' => false,
                'class' => 'form-control form-control-sm',
                'templates' => ['inputContainer' => '{{content}}']
            ]); ?>
            <?= $this->Html->link(__('New User'), ['action' => 'add'], ['class' => 'btn btn-primary btn-sm ml-2']) ?>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                                        <th class="actions"><?= __('Actions') ?></th>

                    <th><?= $this->Paginator->sort('username') ?></th>
                    <th><?= $this->Paginator->sort('display_name') ?></th>
                    <th><?= $this->Paginator->sort('email') ?></th>
                    <th><?= $this->Paginator->sort('kintone_username', __('Kintone Username')) ?></th>
                    <th><?= $this->Paginator->sort('is_kintone_linked', __('Kintone')) ?></th>
                    <th><?= $this->Paginator->sort('role_id') ?></th>
                    <th><?= $this->Paginator->sort('is_active') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('created_by') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th><?= $this->Paginator->sort('modified_by') ?></th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($users as $user) : ?>
                    <tr>
                                                <td class="actions text-nowrap">
                            <?= $this->Html->link(
                                '<i class="fas fa-eye"></i>',
                                ['action' => 'view', $user->id],
                                [
                                    'class' => 'btn btn-xs btn-outline-info',
                                    'escape' => false,
                                    'title' => __('View'),
                                    'data-toggle' => 'tooltip',
                                ]
                            ) ?>

                            <?= $this->Html->link(
                                '<i class="fas fa-edit"></i>',
                                ['action' => 'edit', $user->id],
                                [
                                    'class' => 'btn btn-xs btn-outline-primary ml-1',
                                    'escape' => false,
                                    'title' => __('Edit'),
                                    'data-toggle' => 'tooltip',
                                ]
                            ) ?>

                            <?= $this->Form->postLink(
                                '<i class="fas fa-trash"></i>',
                                ['action' => 'delete', $user->id],
                                [
                                    'class' => 'btn btn-xs btn-outline-danger ml-1',
                                    'escape' => false,
                                    'confirm' => __('Are you sure you want to delete # {0}?', $user->id),
                                    'title' => __('Delete'),
                                    'data-toggle' => 'tooltip',
                                ]
                            ) ?>
                        </td>

                        <td><?= h($user->username) ?></td>
                        <td><?= h($user->display_name) ?></td>
                        <td><?= h($user->email) ?></td>
                        <td><?= h($user->kintone_username) ?></td>
                        <td>
                            <?php if ($user->is_kintone_linked) : ?>
                                <span class="badge badge-success"><i class="fas fa-check-circle"></i></span>
                            <?php elseif (!empty($user->kintone_username)) : ?>
                                <span class="badge badge-warning"><i class="fas fa-unlink"></i></span>
                            <?php else : ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $user->has('role') ? $this->Html->link($user->role->display_name, ['controller' => 'Roles', 'action' => 'view', $user->role->id]) : '' ?></td>
                        <td><?= ($user->is_active) ? __('Yes') : __('No') ?></td>
                        <td><?= h($user->created) ?></td>
                        <td><?= $user->has('created_by_user') ? $this->Html->link($user->created_by_user->display_name, ['controller' => 'Users', 'action' => 'view', $user->created_by_user->id]) : '' ?></td>
                        <td><?= h($user->modified) ?></td>
                        <td><?= $user->has('modified_by_user') ? $this->Html->link($user->modified_by_user->display_name, ['controller' => 'Users', 'action' => 'view', $user->modified_by_user->id]) : '' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card-footer d-flex flex-column flex-md-row">
        <div class="text-muted">
            <?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?>
        </div>
        <ul class="pagination pagination-sm mb-0 ml-auto">
            <?= $this->Paginator->first('<i class="fas fa-angle-double-left"></i>', ['escape' => false]) ?>
            <?= $this->Paginator->prev('<i class="fas fa-angle-left"></i>', ['escape' => false]) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next('<i class="fas fa-angle-right"></i>', ['escape' => false]) ?>
            <?= $this->Paginator->last('<i class="fas fa-angle-double-right"></i>', ['escape' => false]) ?>
        </ul>
    </div>
</div>
