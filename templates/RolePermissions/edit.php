<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RolePermission $rolePermission
 */
?>

<?php
$this->assign('title', __('Edit Role Permission'));
$this->Breadcrumbs->addMany([
    ['title' => __('Home'), 'url' => '/'],
    ['title' => __('List Role Permissions'), 'url' => ['action' => 'index']],
    ['title' => __('View'), 'url' => ['action' => 'view', $rolePermission->id]],
    ['title' => __('Edit')],
]);
?>

<div class="card card-primary card-outline">
    <?= $this->Form->create($rolePermission) ?>
    <div class="card-body">



                                <?= $this->Form->control('role_id', ['options' => $roles, 'class' => 'form-control']) ?>
        
    


        
                            
                                <?= $this->Form->control('plugin') ?>
            
            


        
                            
                                <?= $this->Form->control('prefix') ?>
            
            


        
                            
                                <?= $this->Form->control('controller') ?>
            
            


        
                            
                                <?= $this->Form->control('action') ?>
            
            


        
                            
                                <?= $this->Form->control('allowed', ['custom' => true]) ?>
            
            


        
                    



        
                    


    </div>
    <div class="card-footer d-flex">
        <div class="mr-auto">
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $rolePermission->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $rolePermission->id), 'class' => 'btn btn-danger']
            ) ?>
        </div>
        <div class="ml-auto">
            <?= $this->Form->button(__('Save'), ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'view', $rolePermission->id], ['class' => 'btn btn-default']) ?>
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
