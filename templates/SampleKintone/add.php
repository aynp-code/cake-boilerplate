<?php
/**
 * @var \App\View\AppView $this
 * @var array<int, string> $approvalOptions
 * @var array<int, string> $categoryOptions
 * @var array<int, string> $tagOptions
 */
$this->assign('title', 'Sample Kintone 新規登録');
$this->Breadcrumbs->addMany([
    ['title' => 'Home', 'url' => '/'],
    ['title' => 'Sample Kintone 一覧', 'url' => ['action' => 'index']],
    ['title' => '新規登録'],
]);
?>

<div class="card card-primary card-outline">
    <?= $this->Form->create(null, ['url' => ['action' => 'add'], 'valueSources' => ['query', 'context']]) ?>

    <div class="card-body">

        <?php // ① 承認（ドロップダウン・必須） ?>
        <?= $this->Form->control('approval', [
            'options' => array_combine($approvalOptions, $approvalOptions),
            'required' => true,
            'class' => 'form-control'
        ]) ?>

        <?php // ② 種別（ラジオボタン・初期値:什器） ?>
        <?= $this->Form->control('category', [
            'options' => array_combine($categoryOptions, $categoryOptions),
            'type' => 'radio',
            'required' => true
        ]) ?>

        <?php // ③ タグ（チェックボックス・複数選択） ?>
        <?= $this->Form->control('tags', [
            'options' => array_combine($tagOptions, $tagOptions),
            'multiple' => 'checkbox',
        ]) ?>

        <?php // ④ 発売日（日付・必須） ?>
        <?= $this->Form->control('release_date', [
            'type'  => 'date',
            'label' => '発売日',
            'required' => true,
        ]) ?>

        <?php // ⑤ 商品URL（リンク） ?>
        <?= $this->Form->control('product_url', [
            'type'        => 'url',
            'label'       => '商品URL',
            'placeholder' => 'https://example.com',
        ]) ?>

        <?php // ⑥ 型番（文字列1行・必須・重複禁止・最大64文字） ?>
        <?= $this->Form->control('model_number', [
            'label'     => '型番',
            'maxlength' => 64,
            'required' => true,
        ]) ?>

        <?php // ⑦ 商品名（文字列1行） ?>
        <?= $this->Form->control('product_name', [
            'label' => '商品名',
            'required' => true,
        ]) ?>

        <?php // ⑧ 価格（数値・必須） ?>
        <?= $this->Form->control('price', [
            'type'  => 'number',
            'label' => '価格',
            'min'   => 0,
            'step'  => 1,
            'required' => true,
        ]) ?>

        <?php // ⑨ 特記事項（文字列複数行） ?>
        <?= $this->Form->control('notes', [
            'type'  => 'textarea',
            'label' => '特記事項',
            'rows'  => 4,
        ]) ?>

        <?php // ⑩ 添付ファイルはkintone側で管理するためフォームには表示しない ?>

    </div>

    <div class="card-footer d-flex">
        <div class="ml-auto">
            <?= $this->Html->tag('button', '保存', ['type' => 'submit', 'class' => 'btn btn-primary', 'escape' => false]) ?>
            <?= $this->Html->link('キャンセル', ['action' => 'index'], ['class' => 'btn btn-default ml-1']) ?>
        </div>
    </div>

    <?= $this->Form->end() ?>
</div>
