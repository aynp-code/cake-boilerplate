<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\NotFoundException;

class RolePermissionsController extends AppController
{
    public function index()
    {
        // role_id で絞り込み（?role_id=...）
        $roleId = $this->request->getQuery('role_id');

        $query = $this->RolePermissions->find()
            ->contain(['Roles'])
            ->orderBy([
                'Roles.id' => 'ASC',
                'plugin' => 'ASC',
                'prefix' => 'ASC',
                'controller' => 'ASC',
                'action' => 'ASC',
            ]);

        if ($roleId) {
            $query->where(['RolePermissions.role_id' => $roleId]);
        }

        $rolePermissions = $this->paginate($query);

        // 絞り込み用のroles
        $roles = $this->RolePermissions->Roles->find('list')->all();

        $this->set(compact('rolePermissions', 'roles', 'roleId'));
    }

    public function add()
    {
        $rolePermission = $this->RolePermissions->newEmptyEntity();

        if ($this->request->is('post')) {
            $rolePermission = $this->RolePermissions->patchEntity($rolePermission, $this->request->getData());
            if ($this->RolePermissions->save($rolePermission)) {
                $this->Flash->success(__('The permission has been saved.'));
                return $this->redirect(['action' => 'index', '?' => ['role_id' => $rolePermission->role_id]]);
            }
            $this->Flash->error(__('The permission could not be saved. Please, try again.'));
        }

        $roles = $this->RolePermissions->Roles->find('list')->all();
        $this->set(compact('rolePermission', 'roles'));
    }

    public function edit(string $id)
    {
        $rolePermission = $this->RolePermissions->find()
            ->where(['RolePermissions.id' => $id])
            ->first();

        if (!$rolePermission) {
            throw new NotFoundException();
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $rolePermission = $this->RolePermissions->patchEntity($rolePermission, $this->request->getData());
            if ($this->RolePermissions->save($rolePermission)) {
                $this->Flash->success(__('The permission has been saved.'));
                return $this->redirect(['action' => 'index', '?' => ['role_id' => $rolePermission->role_id]]);
            }
            $this->Flash->error(__('The permission could not be saved. Please, try again.'));
        }

        $roles = $this->RolePermissions->Roles->find('list')->all();
        $this->set(compact('rolePermission', 'roles'));
    }

    public function delete(string $id)
    {
        $this->request->allowMethod(['post', 'delete']);

        $rolePermission = $this->RolePermissions->find()
            ->where(['RolePermissions.id' => $id])
            ->first();

        if (!$rolePermission) {
            throw new NotFoundException();
        }

        $roleId = $rolePermission->role_id;

        if ($this->RolePermissions->delete($rolePermission)) {
            $this->Flash->success(__('The permission has been deleted.'));
        } else {
            $this->Flash->error(__('The permission could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index', '?' => ['role_id' => $roleId]]);
    }
}
