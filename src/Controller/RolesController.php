<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Roles Controller
 *
 * @property \App\Model\Table\RolesTable $Roles
 */
class RolesController extends AppController
{
    public function index()
    {
        // ✅ 監査ユーザ（CreatedByUser/ModifiedByUser）は AppTable 側で contain を拡張して共通化
        $contain = $this->Roles->withAuditUsersContain([]);

        $query = $this->Roles->find()
            ->contain($contain);

        $roles = $this->paginate($query);
        $this->set(compact('roles'));
    }

    public function view($id = null)
    {
        $contain = $this->Roles->withAuditUsersContain(['RolePermissions', 'Users']);
        $role = $this->Roles->get($id, contain: $contain);

        $this->set(compact('role'));
    }

    public function add()
    {
        $role = $this->Roles->newEmptyEntity();

        if ($this->request->is('post')) {
            // ✅ Roles は validationCreate 前提にしない（ボイラープレート事故防止）
            $role = $this->Roles->patchEntity($role, $this->request->getData());

            if ($this->Roles->save($role)) {
                $this->Flash->success(__('The role has been saved.'));
                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('The role could not be saved. Please, try again.'));
        }

        $this->set(compact('role'));
    }

    public function edit($id = null)
    {
        $role = $this->Roles->get($id, contain: []);

        // ※ Roles に password は存在しない想定なので、ここで触らない

        if ($this->request->is(['patch', 'post', 'put'])) {
            $role = $this->Roles->patchEntity($role, $this->request->getData());

            if ($this->Roles->save($role)) {
                $this->Flash->success(__('The role has been saved.'));
                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('The role could not be saved. Please, try again.'));
        }

        $this->set(compact('role'));
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $role = $this->Roles->get($id);

        if ($this->Roles->delete($role)) {
            $this->Flash->success(__('The role has been deleted.'));
        } else {
            $this->Flash->error(__('The role could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
