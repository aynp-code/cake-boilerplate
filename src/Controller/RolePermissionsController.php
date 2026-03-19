<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\RolePermissionMatrixService;

/**
 * RolePermissions Controller
 *
 * @property \App\Model\Table\RolePermissionsTable $RolePermissions
 */
class RolePermissionsController extends AppController
{
    private RolePermissionMatrixService $matrixService;

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        // Controller内にnewが散らばらないように寄せる（簡易DI）
        $this->matrixService = new RolePermissionMatrixService();
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        // ✅ 監査ユーザ（CreatedByUser/ModifiedByUser）は AppTable 側で contain を拡張して共通化
        $contain = $this->RolePermissions->withAuditUsersContain(['Roles']);

        $query = $this->RolePermissions->find()
            ->contain($contain);

        $rolePermissions = $this->paginate($query);

        $this->set(compact('rolePermissions'));
    }

    /**
     * View method
     *
     * @param string|null $id Role Permission id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        // ✅ 監査ユーザ（CreatedByUser/ModifiedByUser）は AppTable 側で contain を拡張して共通化
        $contain = $this->RolePermissions->withAuditUsersContain(['Roles']);

        $rolePermission = $this->RolePermissions->get($id, contain: $contain);
        $this->set(compact('rolePermission'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $rolePermission = $this->RolePermissions->newEmptyEntity();

        if ($this->request->is('post')) {
            $rolePermission = $this->RolePermissions->patchEntity(
                $rolePermission,
                $this->request->getData(),
                ['validate' => 'create'],
            );

            if ($this->RolePermissions->save($rolePermission)) {
                $this->Flash->success(__('The role permission has been saved.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('The role permission could not be saved. Please, try again.'));
        }

        $roles = $this->RolePermissions->Roles->find('list', limit: 200)->all();
        $this->set(compact('rolePermission', 'roles'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Role Permission id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $rolePermission = $this->RolePermissions->get($id, contain: []);

        // ※ RolePermissions に password は存在しない想定なので、ここで触らない

        if ($this->request->is(['patch', 'post', 'put'])) {
            $rolePermission = $this->RolePermissions->patchEntity($rolePermission, $this->request->getData());

            if ($this->RolePermissions->save($rolePermission)) {
                $this->Flash->success(__('The role permission has been saved.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('The role permission could not be saved. Please, try again.'));
        }

        $roles = $this->RolePermissions->Roles->find('list', limit: 200)->all();
        $this->set(compact('rolePermission', 'roles'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Role Permission id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $rolePermission = $this->RolePermissions->get($id);

        if ($this->RolePermissions->delete($rolePermission)) {
            $this->Flash->success(__('The role permission has been deleted.'));
        } else {
            $this->Flash->error(__('The role permission could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Matrix method - display and save the permission matrix.
     *
     * @return \Cake\Http\Response|null|void Redirects on successful save, renders view otherwise.
     */
    public function matrix()
    {
        if ($this->request->is('post')) {
            $perm = $this->request->getData('perm') ?? [];
            $this->matrixService->save((array)$perm);

            $this->Flash->success(__('Permissions updated.'));

            return $this->redirect(['action' => 'matrix']);
        }

        $vm = $this->matrixService->buildViewModel();
        $this->set($vm);
    }
}
