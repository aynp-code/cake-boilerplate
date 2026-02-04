<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use App\Service\RolePermissionChecker;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    protected RolePermissionChecker $permissionChecker;

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');
        $this->permissionChecker = new RolePermissionChecker();
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $controller = (string)$this->request->getParam('controller');
        $action     = (string)$this->request->getParam('action');
        $plugin     = $this->request->getParam('plugin'); // nullが多い
        $prefix     = $this->request->getParam('prefix'); // nullが多い

        // login/logout は常に許可（無限リダイレクト防止）
        if ($controller === 'Users' && in_array($action, ['login', 'logout'], true)) {
            return;
        }

        $identity = $this->request->getAttribute('identity');
        if (!$identity) {
            // 未認証は Authentication が redirect する想定
            return;
        }

        $roleId = $identity->get('role_id');
        if (!$roleId) {
            throw new ForbiddenException('Role is not assigned.');
        }

        $allowed = $this->permissionChecker->can((string)$roleId, [
            'plugin' => $plugin,
            'prefix' => $prefix,
            'controller' => $controller,
            'action' => $action,
        ]);

        if (!$allowed) {
            throw new ForbiddenException('You are not allowed to access this resource.');
        }
    }

    /*
    * ビュー描画前のフック処理です。
    *
    * 全てのアクションで CakeLte のデフォルトレイアウトを使用するため、
    * レンダリング前にレイアウトを明示的に設定します。
    */
    public function beforeRender(\Cake\Event\EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->setLayout('CakeLte.default');
    }
}
