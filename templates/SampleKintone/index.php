<?php
/**
 * @var \App\View\AppView $this
 * @var array<int, array<string, mixed>> $records
 */
$this->assign('title', 'Sample Kintone');
$this->Breadcrumbs->addMany([
    ['title' => 'Home', 'url' => '/'],
    ['title' => 'Sample Kintone 一覧'],
]);
?>

<div class="card card-primary card-outline">
    <div class="card-header d-flex flex-column flex-md-row">
        <h2 class="card-title"></h2>
        <div class="d-flex ml-auto">
            <?= $this->Html->link('新規登録', ['action' => 'add'], ['class' => 'btn btn-primary btn-sm ml-2']) ?>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th class="actions">操作</th>
                    <th>ID</th>
                    <th>承認</th>
                    <th>種別</th>
                    <th>型番</th>
                    <th>商品名</th>
                    <th>価格</th>
                    <th>発売日</th>
                    <th>タグ</th>
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
                                    'title'       => '詳細',
                                    'data-toggle' => 'tooltip',
                                ]
                            ) ?>
                            <?= $this->Html->link(
                                '<i class="fas fa-edit"></i>',
                                ['action' => 'edit', $record['id']],
                                [
                                    'class'       => 'btn btn-xs btn-outline-primary ml-1',
                                    'escape'      => false,
                                    'title'       => '編集',
                                    'data-toggle' => 'tooltip',
                                ]
                            ) ?>
                            <?= $this->Form->postLink(
                                '<i class="fas fa-trash"></i>',
                                ['action' => 'delete', $record['id']],
                                [
                                    'class'       => 'btn btn-xs btn-outline-danger ml-1',
                                    'escape'      => false,
                                    'confirm'     => 'ID: ' . $record['id'] . ' を削除しますか？',
                                    'title'       => '削除',
                                    'data-toggle' => 'tooltip',
                                ]
                            ) ?>
                        </td>
                        <td><?= h($record['id']) ?></td>
                        <td>
                            <?php if ($record['approval'] === '承認') : ?>
                                <span class="badge badge-success">承認</span>
                            <?php elseif ($record['approval'] === '未承認') : ?>
                                <span class="badge badge-warning">未承認</span>
                            <?php else : ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= h($record['category']) ?: '—' ?></td>
                        <td><?= h($record['model_number']) ?: '—' ?></td>
                        <td><?= h($record['product_name']) ?: '—' ?></td>
                        <td><?= $record['price'] !== null ? '¥' . number_format($record['price']) : '—' ?></td>
                        <td><?= h($record['release_date']) ?: '—' ?></td>
                        <td>
                            <?php foreach ($record['tags'] as $tag) : ?>
                                <span class="badge badge-secondary mr-1"><?= h($tag) ?></span>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($records)) : ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-3">データがありません</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
