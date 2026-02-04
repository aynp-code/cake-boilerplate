<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->allowUnauthenticated(['login', 'logout']);
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {


        $query = $this->Users->find()
            ->contain(['CreatedByUser', 'ModifiedByUser', 'Roles']);
        $users = $this->paginate($query);

        $this->set(compact('users'));
    }


    /**
     * View method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $user = $this->Users->get($id, contain: ['CreatedByUser', 'ModifiedByUser', 'Roles']);
        $this->set(compact('user'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $user = $this->Users->newEmptyEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData(), [
                'validate' => 'create',
            ]);
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $createdByUser = $this->Users->CreatedByUser->find('list', limit: 200)->all();
        $modifiedByUser = $this->Users->ModifiedByUser->find('list', limit: 200)->all();
        $roles = $this->Users->Roles->find('list', limit: 200)->all();
        $this->set(compact('user', 'createdByUser', 'modifiedByUser', 'roles'));
    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $user = $this->Users->get($id, contain: []);
        $user->set('password', '');
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // ✅ edit では未入力なら password を更新しない
            if (array_key_exists('password', $data) && $data['password'] === '') {
                unset($data['password']);
            }

            $user = $this->Users->patchEntity($user, $data);
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $createdByUser = $this->Users->CreatedByUser->find('list', limit: 200)->all();
        $modifiedByUser = $this->Users->ModifiedByUser->find('list', limit: 200)->all();
        $roles = $this->Users->Roles->find('list', limit: 200)->all();
        $this->set(compact('user', 'createdByUser', 'modifiedByUser', 'roles'));
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function login()
    {
        $this->request->allowMethod(['get', 'post']);
        $result = $this->Authentication->getResult();
        // POSTで認証成功したらリダイレクト
        if ($this->request->is('post') && $result && $result->isValid()) {
            $target = $this->Authentication->getLoginRedirect() ?? [
                'controller' => 'Pages',
                'action' => 'display',
                'home',
            ];
            return $this->redirect($target);
        }
        // 失敗時
        if ($this->request->is('post') && (!$result || !$result->isValid())) {
            $this->Flash->error(__('Invalid username or password'));
        }
    }

    public function logout()
    {
        $this->request->allowMethod(['post', 'get']);
        $this->Authentication->logout();
        return $this->redirect(['action' => 'login']);
    }
}
