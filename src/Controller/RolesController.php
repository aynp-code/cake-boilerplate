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
    /**
     * Index method.
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        // ✅ 監査ユーザ（CreatedByUser/ModifiedByUser）は AppTable 側で contain を拡張して共通化
        $contain = $this->Roles->withAuditUsersContain([]);

        $query = $this->Roles->find()
            ->contain($contain);

        $roles = $this->paginate($query);
        $this->set(compact('roles'));
    }

    /**
     * View method.
     *
     * @param string $id Role id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(string $id)
    {
        $contain = $this->Roles->withAuditUsersContain(['RolePermissions', 'Users']);
        $role = $this->Roles->get($id, contain: $contain);

        $this->set(compact('role'));
    }

    /**
     * Add method.
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
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

    /**
     * Edit method.
     *
     * @param string $id Role id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(string $id)
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

    /**
     * Delete method.
     *
     * @param string $id Role id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(string $id)
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
