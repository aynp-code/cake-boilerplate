<?php
declare(strict_types=1);

namespace App\Controller;

use PDOException;

/**
 * Roles Controller
 *
 * @property \App\Model\Table\RolesTable $Roles
 */
class RolesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {


        $query = $this->Roles->find()
            ->contain(['CreatedByUser', 'ModifiedByUser']);
        $roles = $this->paginate($query);

        $this->set(compact('roles'));
    }


    /**
     * View method
     *
     * @param string|null $id Role id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $role = $this->Roles->get($id, contain: ['CreatedByUser', 'ModifiedByUser', 'Users']);
        $this->set(compact('role'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $role = $this->Roles->newEmptyEntity();
        if ($this->request->is('post')) {
            $role = $this->Roles->patchEntity($role, $this->request->getData(), [
                'validate' => 'create',
            ]);
            if ($this->Roles->save($role)) {
                $this->Flash->success(__('The role has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The role could not be saved. Please, try again.'));
        }
        $createdByUser = $this->Roles->CreatedByUser->find('list', limit: 200)->all();
        $modifiedByUser = $this->Roles->ModifiedByUser->find('list', limit: 200)->all();
        $this->set(compact('role', 'createdByUser', 'modifiedByUser'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Role id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $role = $this->Roles->get($id, contain: []);
        $role->set('password', '');
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // ✅ edit では未入力なら password を更新しない
            if (array_key_exists('password', $data) && $data['password'] === '') {
                unset($data['password']);
            }

            $role = $this->Roles->patchEntity($role, $data);
            if ($this->Roles->save($role)) {
                $this->Flash->success(__('The role has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The role could not be saved. Please, try again.'));
        }
        $createdByUser = $this->Roles->CreatedByUser->find('list', limit: 200)->all();
        $modifiedByUser = $this->Roles->ModifiedByUser->find('list', limit: 200)->all();
        $this->set(compact('role', 'createdByUser', 'modifiedByUser'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Role id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(string $id)
    {
        $this->request->allowMethod(['post', 'delete']);

        $role = $this->Roles->get($id);

        // 1) 事前チェック（FKで落ちる前に止める）
        $inUse = $this->Roles->Users->find()
            ->where(['Users.role_id' => $id])
            ->count();

        if ($inUse > 0) {
            $this->Flash->error(__('This role is assigned to {0} user(s) and cannot be deleted.', [$inUse]));
            return $this->redirect(['action' => 'index']);
        }

        // 2) DB例外も念のため拾う（レースコンディション対策）
        try {
            if ($this->Roles->delete($role)) {
                $this->Flash->success(__('The role has been deleted.'));
            } else {
                $this->Flash->error(__('The role could not be deleted. Please try again.'));
            }
        } catch (PDOException $e) {
            $this->Flash->error(__('This role cannot be deleted because it is in use.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
