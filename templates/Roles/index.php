<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Role[]|\Cake\Collection\CollectionInterface $roles
 */
?>

<?php
$this->assign('title', __('Roles'));
$this->Breadcrumbs->add([
    ['title' => __('Home'), 'url' => '/'],
    ['title' => __('List Roles')],
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
            <?= $this->Html->link(__('New Role'), ['action' => 'add'], ['class' => 'btn btn-primary btn-sm ml-2']) ?>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                                        <th class="actions"><?= __('Actions') ?></th>

                    <th><?= $this->Paginator->sort('display_name') ?></th>
                    <th><?= $this->Paginator->sort('description') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('created_by') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th><?= $this->Paginator->sort('modified_by') ?></th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($roles as $role) : ?>
                    <tr>
                                                <td class="actions text-nowrap">
                            <?= $this->Html->link(
                                '<i class="fas fa-eye"></i>',
                                ['action' => 'view', $role->id],
                                [
                                    'class' => 'btn btn-xs btn-outline-info',
                                    'escape' => false,
                                    'title' => __('View'),
                                    'data-toggle' => 'tooltip',
                                ]
                            ) ?>

                            <?= $this->Html->link(
                                '<i class="fas fa-edit"></i>',
                                ['action' => 'edit', $role->id],
                                [
                                    'class' => 'btn btn-xs btn-outline-primary ml-1',
                                    'escape' => false,
                                    'title' => __('Edit'),
                                    'data-toggle' => 'tooltip',
                                ]
                            ) ?>

                            <?= $this->Form->postLink(
                                '<i class="fas fa-trash"></i>',
                                ['action' => 'delete', $role->id],
                                [
                                    'class' => 'btn btn-xs btn-outline-danger ml-1',
                                    'escape' => false,
                                    'confirm' => __('Are you sure you want to delete # {0}?', $role->id),
                                    'title' => __('Delete'),
                                    'data-toggle' => 'tooltip',
                                ]
                            ) ?>
                        </td>

                        <td><?= h($role->display_name) ?></td>
                        <td><?= h($role->description) ?></td>
                        <td><?= h($role->created) ?></td>
                        <td><?= h($role->created_by) ?></td>
                        <td><?= h($role->modified) ?></td>
                        <td><?= h($role->modified_by) ?></td>
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
