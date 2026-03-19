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
    <?= $this->Form->create(null, ['url' => ['action' => 'edit', $record['id']], 'enctype' => 'multipart/form-data']) ?>

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

        <?php // ⑩ 添付ファイル（既存ファイル削除 + 新規アップロード） ?>
        <div class="form-group">
            <label>添付ファイル（既存）</label>
            <?php if (!empty($record['attachments'])) : ?>
                <div class="mb-2">
                    <?php foreach ($record['attachments'] as $file) : ?>
                        <?php
                            $fileKey  = h($file['fileKey'] ?? '');
                            $fileName = h($file['name'] ?? '');
                            $fileSize = !empty($file['size'])
                                ? number_format((int)$file['size'] / 1024, 1) . ' KB'
                                : '';
                            $checkId  = 'del_' . $fileKey;
                        ?>
                        <div class="d-flex align-items-center mb-1" id="file-row-<?= $fileKey ?>">
                            <?php // 削除しない場合は hidden で fileKey を送信する ?>
                            <input
                                type="hidden"
                                name="existing_file_keys[]"
                                value="<?= $fileKey ?>"
                                id="hidden-<?= $fileKey ?>"
                            >
                            <i class="fas fa-paperclip mr-2 text-muted"></i>
                            <span class="mr-2"><?= $fileName ?></span>
                            <?php if ($fileSize) : ?>
                                <small class="text-muted mr-3">(<?= $fileSize ?>)</small>
                            <?php endif; ?>
                            <button
                                type="button"
                                class="btn btn-xs btn-outline-danger"
                                onclick="removeExistingFile('<?= $fileKey ?>')"
                                title="削除"
                                data-toggle="tooltip"
                            >
                                <i class="fas fa-times"></i> 削除
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="form-control-plaintext text-muted mb-1">添付ファイルなし</p>
            <?php endif; ?>

            <label class="mt-2">添付ファイル（新規追加）</label>
            <div class="custom-file">
                <input
                    type="file"
                    name="attachments[]"
                    id="attachments"
                    class="custom-file-input"
                    multiple
                >
                <label class="custom-file-label" for="attachments">ファイルを選択...</label>
            </div>
            <small class="form-text text-muted">複数ファイルを同時に選択できます。</small>
        </div>

        <script>
        /**
         * 既存ファイルの削除ボタンを押したときの処理
         * - hidden input を削除することで POST 時に fileKey が送信されなくなる（= kintone 側で削除される）
         * - 行全体を非表示にしてユーザーに削除済みであることを示す
         */
        function removeExistingFile(fileKey) {
            const row    = document.getElementById('file-row-' + fileKey);
            const hidden = document.getElementById('hidden-' + fileKey);
            if (hidden) hidden.remove();
            if (row)    row.style.display = 'none';
        }

        // custom-file-input のラベルにファイル名を表示する
        document.getElementById('attachments').addEventListener('change', function () {
            const files = Array.from(this.files).map(f => f.name).join(', ');
            this.nextElementSibling.textContent = files || 'ファイルを選択...';
        });
        </script>

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