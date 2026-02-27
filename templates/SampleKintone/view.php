<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, mixed> $record
 */
$this->assign('title', __('Sample Kintone'));
$this->Breadcrumbs->addMany([
    ['title' => __('Home'), 'url' => '/'],
    ['title' => __('List Sample Kintone'), 'url' => ['action' => 'index']],
    ['title' => __('View')],
]);
?>

<div class="view card card-primary card-outline">
    <div class="card-header d-sm-flex">
        <h2 class="card-title"><?= h($record['product_name']) ?></h2>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <tr>
                <th><?= __('ID') ?></th>
                <td><?= h($record['id']) ?></td>
            </tr>
            <tr>
                <th><?= __('サービス種別') ?></th>
                <td><?= h($record['service_type']) ?></td>
            </tr>
            <tr>
                <th><?= __('型番') ?></th>
                <td><?= h($record['model_number']) ?: '—' ?></td>
            </tr>
            <tr>
                <th><?= __('商品名') ?></th>
                <td><?= h($record['product_name']) ?: '—' ?></td>
            </tr>
            <tr>
                <th><?= __('価格') ?></th>
                <td><?= $record['price'] !== null ? number_format($record['price']) . ' 円' : '—' ?></td>
            </tr>
            <tr>
                <th><?= __('特記事項') ?></th>
                <td><?= nl2br(h($record['notes'])) ?: '—' ?></td>
            </tr>
        </table>
    </div>

    <div class="card-footer d-flex">
        <div class="mr-auto">
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $record['id']],
                ['confirm' => __('Are you sure you want to delete # {0}?', $record['id']), 'class' => 'btn btn-danger']
            ) ?>
        </div>
        <div class="ml-auto">
            <?= $this->Html->link(__('Edit'),   ['action' => 'edit', $record['id']], ['class' => 'btn btn-secondary']) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index'],               ['class' => 'btn btn-default']) ?>
        </div>
    </div>
</div>
