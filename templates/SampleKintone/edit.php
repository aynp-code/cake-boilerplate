<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, mixed> $record
 * @var array<int, string> $serviceTypes
 */
$this->assign('title', __('Edit Sample Kintone'));
$this->Breadcrumbs->addMany([
    ['title' => __('Home'), 'url' => '/'],
    ['title' => __('List Sample Kintone'), 'url' => ['action' => 'index']],
    ['title' => __('View'), 'url' => ['action' => 'view', $record['id']]],
    ['title' => __('Edit')],
]);
?>

<div class="card card-primary card-outline">
    <?= $this->Form->create(null, ['url' => ['action' => 'edit', $record['id']]]) ?>
    <div class="card-body">
        <?= $this->Form->control('service_type', [
            'type'    => 'select',
            'label'   => 'サービス種別',
            'options' => array_combine($serviceTypes, $serviceTypes),
            'value'   => $record['service_type'],
        ]) ?>
        <?= $this->Form->control('model_number', ['label' => '型番',   'value' => $record['model_number']]) ?>
        <?= $this->Form->control('product_name', ['label' => '商品名', 'value' => $record['product_name']]) ?>
        <?= $this->Form->control('price', ['type' => 'number', 'label' => '価格', 'value' => $record['price'], 'min' => 0]) ?>
        <?= $this->Form->control('notes', ['type' => 'textarea', 'label' => '特記事項', 'value' => $record['notes'], 'rows' => 4]) ?>
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
            <?= $this->Html->tag('button', __('Save'), ['type' => 'submit', 'class' => 'btn btn-primary', 'escape' => false]) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'view', $record['id']], ['class' => 'btn btn-default']) ?>
        </div>
    </div>
    <?= $this->Form->end() ?>
</div>
