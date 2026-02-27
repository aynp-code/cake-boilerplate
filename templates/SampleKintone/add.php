<?php
/**
 * @var \App\View\AppView $this
 * @var array<int, string> $serviceTypes
 */
$this->assign('title', __('Add Sample Kintone'));
$this->Breadcrumbs->addMany([
    ['title' => __('Home'), 'url' => '/'],
    ['title' => __('List Sample Kintone'), 'url' => ['action' => 'index']],
    ['title' => __('Add')],
]);
?>

<div class="card card-primary card-outline">
    <?= $this->Form->create(null, ['url' => ['action' => 'add'], 'valueSources' => ['query', 'context']]) ?>
    <div class="card-body">
        <?= $this->Form->control('service_type', [
            'type'    => 'select',
            'label'   => 'サービス種別',
            'options' => array_combine($serviceTypes, $serviceTypes),
            'default' => 'kintone',
        ]) ?>
        <?= $this->Form->control('model_number', ['label' => '型番']) ?>
        <?= $this->Form->control('product_name', ['label' => '商品名']) ?>
        <?= $this->Form->control('price', ['type' => 'number', 'label' => '価格', 'min' => 0]) ?>
        <?= $this->Form->control('notes', ['type' => 'textarea', 'label' => '特記事項', 'rows' => 4]) ?>
    </div>
    <div class="card-footer d-flex">
        <div class="ml-auto">
            <?= $this->Html->tag('button', __('Save'), ['type' => 'submit', 'class' => 'btn btn-primary', 'escape' => false]) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>
    <?= $this->Form->end() ?>
</div>
