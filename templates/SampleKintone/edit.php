<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, mixed> $record
 * @var array<int, string> $approvalOptions
 * @var array<int, string> $categoryOptions
 * @var array<int, string> $tagOptions
 */
$this->assign('title', 'Sample Kintone 編集');
$this->Breadcrumbs->addMany([
    ['title' => 'Home', 'url' => '/'],
    ['title' => 'Sample Kintone 一覧', 'url' => ['action' => 'index']],
    ['title' => '詳細', 'url' => ['action' => 'view', $record['id']]],
    ['title' => '編集'],
]);
?>

<div class="card card-primary card-outline">
    <?= $this->Form->create(null, ['url' => ['action' => 'edit', $record['id']]]) ?>

    <div class="card-body">

        <?php // ① 承認（ドロップダウン・必須） ?>
        <?= $this->Form->control('approval', [
            'type'    => 'select',
            'label'   => '承認',
            'options' => array_combine($approvalOptions, $approvalOptions),
            'empty'   => '-- 選択してください --',
            'required' => true,
            'class'   => 'form-control',
        ]) ?>

        <?php // ② 種別（ラジオボタン） ?>
        <?= $this->Form->control('category', [
            'type'    => 'radio',
            'label'   => '種別',
            'required' => true,
            'options' => array_combine($categoryOptions, $categoryOptions),
        ]) ?>

        <?php // ③ タグ（チェックボックス・複数選択） ?>
        <?= $this->Form->control('tags', [
            'type'     => 'select',
            'multiple' => 'checkbox',
            'label'    => 'タグ',
            'options'  => array_combine($tagOptions, $tagOptions),
        ]) ?>

        <?php // ④ 発売日（日付・必須） ?>
        <?= $this->Form->control('release_date', [
            'type'  => 'date',
            'label' => '発売日 *',
            'required' => true,
        ]) ?>

        <?php // ⑤ 商品URL（リンク） ?>
        <?= $this->Form->control('product_url', [
            'type'        => 'url',
            'label'       => '商品URL',
            'placeholder' => 'https://example.com',
        ]) ?>

        <?php // ⑥ 型番（重複禁止のため更新不可・表示のみ） ?>
        <div class="form-group">
            <label>型番</label>
            <input type="text" class="form-control" value="<?= h($record['model_number']) ?>" disabled>
            <small class="form-text text-muted">型番は登録後に変更できません。</small>
        </div>

        <?php // ⑦ 商品名（文字列1行） ?>
        <?= $this->Form->control('product_name', [
            'required' => true,
            'label' => '商品名',
        ]) ?>

        <?php // ⑧ 価格（数値・必須） ?>
        <?= $this->Form->control('price', [
            'type'  => 'number',
            'label' => '価格 *',
            'required' => true,
            'min'   => 0,
            'step'  => 1,
        ]) ?>

        <?php // ⑨ 特記事項（文字列複数行） ?>
        <?= $this->Form->control('notes', [
            'type'  => 'textarea',
            'label' => '特記事項',
            'rows'  => 4,
        ]) ?>

        <?php // ⑩ 添付ファイル（表示のみ・kintone側で管理） ?>
        <div class="form-group">
            <label>添付ファイル</label>
            <?php if (!empty($record['attachments'])) : ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($record['attachments'] as $file) : ?>
                        <li>
                            <i class="fas fa-paperclip mr-1"></i>
                            <?= h($file['name'] ?? '') ?>
                            <?php if (!empty($file['size'])) : ?>
                                <small class="text-muted ml-1">(<?= number_format((int)$file['size'] / 1024, 1) ?> KB)</small>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="form-control-plaintext text-muted">添付ファイルなし</p>
            <?php endif; ?>
            <small class="form-text text-muted">添付ファイルの追加・削除は kintone 画面から行ってください。</small>
        </div>

    </div>

    <div class="card-footer d-flex">
        <div class="mr-auto">
            <?= $this->Form->postLink(
                '削除',
                ['action' => 'delete', $record['id']],
                ['confirm' => 'ID: ' . $record['id'] . ' を削除しますか？', 'class' => 'btn btn-danger']
            ) ?>
        </div>
        <div class="ml-auto">
            <?= $this->Html->tag('button', '保存', ['type' => 'submit', 'class' => 'btn btn-primary', 'escape' => false]) ?>
            <?= $this->Html->link('キャンセル', ['action' => 'view', $record['id']], ['class' => 'btn btn-default ml-1']) ?>
        </div>
    </div>

    <?= $this->Form->end() ?>
</div>