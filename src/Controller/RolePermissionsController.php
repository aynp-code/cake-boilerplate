<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * RolePermissions Controller
 *
 * @property \App\Model\Table\RolePermissionsTable $RolePermissions
 */
class RolePermissionsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {


        $query = $this->RolePermissions->find()
            ->contain(['CreatedByUser', 'ModifiedByUser', 'Roles']);
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
    public function view($id = null)
    {
        // ✅ 監査ユーザ（CreatedByUser/ModifiedByUser）は AppTable 側で contain を拡張して共通化
        $contain = $this->RolePermissions->withAuditUsersContain(['CreatedByUser', 'ModifiedByUser', 'Roles']);

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
            $rolePermission = $this->RolePermissions->patchEntity($rolePermission, $this->request->getData(), [
                'validate' => 'create',
            ]);
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
    public function edit($id = null)
    {
        $rolePermission = $this->RolePermissions->get($id, contain: []);
        $rolePermission->set('password', '');
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // ✅ edit では未入力なら password を更新しない
            if (array_key_exists('password', $data) && $data['password'] === '') {
                unset($data['password']);
            }

            $rolePermission = $this->RolePermissions->patchEntity($rolePermission, $data);
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
    public function delete($id = null)
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
}
