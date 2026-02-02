<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
?>

<?php
$this->assign('title', __('Add User'));
$this->Breadcrumbs->add([
    ['title' => __('Home'), 'url' => '/'],
    ['title' => __('List Users'), 'url' => ['action' => 'index']],
    ['title' => __('Add')],
]);
?>

<div class="card card-primary card-outline">
    <?= $this->Form->create($user, ['valueSources' => ['query', 'context']]) ?>
    <div class="card-body">

        <?= $this->Form->control('username') ?>

        <div class="form-group">
            <?= $this->Form->label('password', __('Password')) ?>

            <div class="input-group">
                <?= $this->Form->password('password', [
                    'class' => 'form-control js-password-input',
                    'autocomplete' => 'new-password',
                    'value' => '',
                ]) ?>

                <div class="input-group-append">
                    <button type="button"
                            class="btn btn-outline-secondary js-password-toggle"
                            aria-label="<?= __('Show password') ?>"
                            aria-pressed="false">
                                                <i class="js-password-icon fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
        <?= $this->Form->control('name') ?>

        <?= $this->Form->control('email') ?>
        <?= $this->Form->control('role_id', ['options' => $roles, 'class' => 'form-control']) ?>

        <?= $this->Form->control('is_active', ['custom' => true]) ?>
    </div>
    <div class="card-footer d-flex">
        <div class="ml-auto">
            <?= $this->Form->button(__('Save'), ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>
    <?= $this->Form->end() ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.js-password-toggle').forEach((btn) => {
    btn.addEventListener('click', () => {
      const group = btn.closest('.input-group');
      if (!group) return;

      const input = group.querySelector('.js-password-input');
      const icon  = btn.querySelector('.js-password-icon');
      if (!input || !icon) return;

      const show = (input.type === 'password');

      // 入力タイプ切替
      input.type = show ? 'text' : 'password';

      // aria
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');

      // 次の操作を示すアイコンに切替
      icon.classList.toggle('fa-eye-slash', show);
      icon.classList.toggle('fa-eye', !show);
    });
  });
});
</script>