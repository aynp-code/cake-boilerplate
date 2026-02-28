<?php
/**
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */

// BootstrapUI が $params['class'] に 'alert alert-success alert-dismissible' などを渡してくる
// そこから alert-* を取り出してアイコンとクラスを決定する
$classes = (array)($params['class'] ?? []);
$alertType = 'info';
foreach ($classes as $cls) {
    if (preg_match('/^alert-(success|danger|warning|info)$/', $cls, $m)) {
        $alertType = $m[1];
        break;
    }
}

$iconMap = [
    'success' => 'fas fa-check-circle',
    'danger'  => 'fas fa-exclamation-circle',
    'warning' => 'fas fa-exclamation-triangle',
    'info'    => 'fas fa-info-circle',
];
$icon = $iconMap[$alertType] ?? $iconMap['info'];

$message = (isset($params['escape']) && $params['escape'] === false) ? $message : h($message);
?>
<div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
    <i class="<?= $icon ?> mr-2"></i>
    <?= $message ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>