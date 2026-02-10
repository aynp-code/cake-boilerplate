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
use App\Service\RoutePermissionTargetNormalizer;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;

/**
 * Application Controller
 *
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    protected RolePermissionChecker $permissionChecker;
    protected RoutePermissionTargetNormalizer $targetNormalizer;

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');

        // 簡易DI（Controller内に散らばらないようにする）
        $this->permissionChecker = new RolePermissionChecker();
        $this->targetNormalizer = new RoutePermissionTargetNormalizer();
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $controller = (string)$this->request->getParam('controller');
        $action     = (string)$this->request->getParam('action');

        $plugin = $this->targetNormalizer->normalizePlugin($this->request->getParam('plugin'));
        $prefix = $this->targetNormalizer->normalizePrefix($this->request->getParam('prefix'));

        // 例外系は素通し（403でエラー画面すら出ない事故を防ぐ）
        if ($controller === 'Error') {
            return;
        }

        // login/logout は常に許可（無限リダイレクト防止）
        if ($controller === 'Users' && in_array($action, ['login', 'logout'], true)) {
            return;
        }

        $identity = $this->request->getAttribute('identity');
        if (!$identity) {
            return; // 未認証は Authentication が redirect する想定
        }

        $roleId = null;
        if (method_exists($identity, 'get')) {
            $roleId = $identity->get('role_id');
        }

        if (!is_string($roleId) || $roleId === '') {
            throw new ForbiddenException('Role is not assigned.');
        }

        $allowed = $this->permissionChecker->can($roleId, [
            'plugin' => $plugin,
            'prefix' => $prefix,
            'controller' => $controller,
            'action' => $action,
        ]);

        if (!$allowed) {
            throw new ForbiddenException('You are not allowed to access this resource.');
        }
    }

    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);

        // CakeLte を全アクションで使う
        $this->viewBuilder()->setLayout('CakeLte.default');
    }
}
