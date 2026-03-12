<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, mixed> $record
 */
$this->assign('title', 'Sample Kintone 詳細');
$this->Breadcrumbs->addMany([
    ['title' => 'Home', 'url' => '/'],
    ['title' => 'Sample Kintone 一覧', 'url' => ['action' => 'index']],
    ['title' => '詳細'],
]);
?>

<div class="view card card-primary card-outline">
    <div class="card-header d-sm-flex">
        <h2 class="card-title"><?= h($record['product_name']) ?: '（商品名未設定）' ?></h2>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <tr>
                <th style="width:160px;">ID</th>
                <td><?= h($record['id']) ?></td>
            </tr>
            <tr>
                <th>承認</th>
                <td>
                    <?php if ($record['approval'] === '承認') : ?>
                        <span class="badge badge-success">承認</span>
                    <?php elseif ($record['approval'] === '未承認') : ?>
                        <span class="badge badge-warning">未承認</span>
                    <?php else : ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>種別</th>
                <td><?= h($record['category']) ?: '—' ?></td>
            </tr>
            <tr>
                <th>タグ</th>
                <td>
                    <?php if (!empty($record['tags'])) : ?>
                        <?php foreach ($record['tags'] as $tag) : ?>
                            <span class="badge badge-secondary mr-1"><?= h($tag) ?></span>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>発売日</th>
                <td><?= h($record['release_date']) ?: '—' ?></td>
            </tr>
            <tr>
                <th>商品URL</th>
                <td>
                    <?php if (!empty($record['product_url'])) : ?>
                        <?= $this->Html->link(
                            h($record['product_url']),
                            $record['product_url'],
                            ['target' => '_blank', 'rel' => 'noopener noreferrer']
                        ) ?>
                    <?php else : ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>型番</th>
                <td><?= h($record['model_number']) ?: '—' ?></td>
            </tr>
            <tr>
                <th>商品名</th>
                <td><?= h($record['product_name']) ?: '—' ?></td>
            </tr>
            <tr>
                <th>価格</th>
                <td><?= $record['price'] !== null ? '¥' . number_format($record['price']) : '—' ?></td>
            </tr>
            <tr>
                <th>特記事項</th>
                <td><?= $record['notes'] !== '' ? nl2br(h($record['notes'])) : '<span class="text-muted">—</span>' ?></td>
            </tr>
            <tr>
                <th>添付ファイル</th>
                <td>
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
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
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
            <?= $this->Html->link('編集',   ['action' => 'edit', $record['id']], ['class' => 'btn btn-secondary']) ?>
            <?= $this->Html->link('一覧へ', ['action' => 'index'],               ['class' => 'btn btn-default ml-1']) ?>
        </div>
    </div>
</div>
