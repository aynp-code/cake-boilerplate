<?php
/**
 * @var \App\View\AppView $this
 */

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

$displayName   = Configure::read('Auth.User.display_name', '');
$currentUserId = Configure::read('Auth.User.id', '');

$isKintoneLinked = false;
$kintoneUsername = '';
if ($currentUserId) {
    try {
        $Users = TableRegistry::getTableLocator()->get('Users');
        $u = $Users->get($currentUserId, contain: []);
        $isKintoneLinked = (bool)$u->is_kintone_linked;
        $kintoneUsername = (string)($u->kintone_username ?? '');
    } catch (\Throwable) {
        // 取得失敗時は未連携として扱う
    }
}
?>

<!-- Left navbar links -->
<ul class="navbar-nav">
    <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
    </li>
</ul>

<!-- Right navbar links -->
<ul class="navbar-nav ml-auto">

    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown"
           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-user-circle fa-lg mr-2"></i>
            <span><?= h($displayName) ?></span>
        </a>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">

            <?php if ($currentUserId) : ?>
                <a class="dropdown-item" href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'edit', $currentUserId]) ?>">
                    <i class="fas fa-user-edit fa-fw mr-2"></i><?= __('Edit Profile') ?>
                </a>

                <div class="dropdown-divider"></div>

                <?php if ($isKintoneLinked) : ?>
                    <span class="dropdown-item-text text-success">
                        <i class="fas fa-check-circle fa-fw mr-2"></i><?= __('Cybozu: Linked') ?>
                    </span>
                    <?= $this->Form->postLink(
                        '<i class="fas fa-unlink fa-fw mr-2"></i>' . __('Disconnect Cybozu'),
                        ['controller' => 'Cybozu', 'action' => 'revoke'],
                        [
                            'class'   => 'dropdown-item text-danger',
                            'escape'  => false,
                            'confirm' => __('Revoke Cybozu link? You will need to re-authenticate.'),
                        ]
                    ) ?>
                <?php elseif ($kintoneUsername !== '') : ?>
                    <span class="dropdown-item-text text-warning">
                        <i class="fas fa-unlink fa-fw mr-2"></i><?= __('Cybozu: Not linked') ?>
                    </span>
                    <a class="dropdown-item" href="<?= $this->Url->build(['controller' => 'Cybozu', 'action' => 'connect']) ?>">
                        <i class="fas fa-link fa-fw mr-2"></i><?= __('Connect Cybozu') ?>
                    </a>
                <?php else : ?>
                    <span class="dropdown-item-text text-muted">
                        <i class="fas fa-minus-circle fa-fw mr-2"></i><?= __('Cybozu: Not configured') ?>
                    </span>
                <?php endif; ?>

                <div class="dropdown-divider"></div>
            <?php endif; ?>

            <?= $this->Form->postLink(
                '<i class="fas fa-sign-out-alt fa-fw mr-2"></i>' . __('Logout'),
                ['controller' => 'Users', 'action' => 'logout'],
                ['class' => 'dropdown-item', 'escape' => false]
            ) ?>

        </div>
    </li>

</ul>