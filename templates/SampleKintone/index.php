<?php
/**
 * @var \App\View\AppView $this
 * @var array<int, array<string, mixed>> $records
 */
$this->assign('title', __('Sample Kintone'));
$this->Breadcrumbs->addMany([
    ['title' => __('Home'), 'url' => '/'],
    ['title' => __('List Sample Kintone')],
]);
?>

<div class="card card-primary card-outline">
    <div class="card-header d-flex flex-column flex-md-row">
        <h2 class="card-title">
            <!-- -->
        </h2>
        <div class="d-flex ml-auto">
            <?= $this->Html->link(__('New'), ['action' => 'add'], ['class' => 'btn btn-primary btn-sm ml-2']) ?>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th class="actions"><?= __('Actions') ?></th>
                    <th><?= __('ID') ?></th>
                    <th><?= __('サービス種別') ?></th>
                    <th><?= __('型番') ?></th>
                    <th><?= __('商品名') ?></th>
                    <th><?= __('価格') ?></th>
                    <th><?= __('特記事項') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record) : ?>
                    <tr>
                        <td class="actions text-nowrap">
                            <?= $this->Html->link(
                                '<i class="fas fa-eye"></i>',
                                ['action' => 'view', $record['id']],
                                [
                                    'class'       => 'btn btn-xs btn-outline-info',
                                    'escape'      => false,
                                    'title'       => __('View'),
                                    'data-toggle' => 'tooltip',
                                ]
                            ) ?>
                            <?= $this->Html->link(
                                '<i class="fas fa-edit"></i>',
                                ['action' => 'edit', $record['id']],
                                [
                                    'class'       => 'btn btn-xs btn-outline-primary ml-1',
                                    'escape'      => false,
                                    'title'       => __('Edit'),
                                    'data-toggle' => 'tooltip',
                                ]
                            ) ?>
                            <?= $this->Form->postLink(
                                '<i class="fas fa-trash"></i>',
                                ['action' => 'delete', $record['id']],
                                [
                                    'class'       => 'btn btn-xs btn-outline-danger ml-1',
                                    'escape'      => false,
                                    'confirm'     => __('Are you sure you want to delete # {0}?', $record['id']),
                                    'title'       => __('Delete'),
                                    'data-toggle' => 'tooltip',
                                ]
                            ) ?>
                        </td>
                        <td><?= h($record['id']) ?></td>
                        <td><?= h($record['service_type']) ?></td>
                        <td><?= h($record['model_number']) ?></td>
                        <td><?= h($record['product_name']) ?></td>
                        <td><?= $record['price'] !== null ? number_format($record['price']) . ' 円' : '—' ?></td>
                        <td class="text-truncate" style="max-width:200px;"><?= h($record['notes']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
