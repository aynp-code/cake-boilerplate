<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\RolePermissionChecker;
use App\Service\RoutePermissionTargetNormalizer;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;

class AppController extends Controller
{
    protected RolePermissionChecker $permissionChecker;
    protected RoutePermissionTargetNormalizer $normalizer;

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');

        $this->permissionChecker = new RolePermissionChecker();
        $this->normalizer = new RoutePermissionTargetNormalizer();
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $controller = (string)$this->request->getParam('controller');
        $action     = (string)$this->request->getParam('action');

        // 例外系は素通し
        if ($controller === 'Error') {
            return;
        }

        // login/logout は常に許可
        if ($controller === 'Users' && in_array($action, ['login', 'logout'], true)) {
            return;
        }

        $identity = $this->request->getAttribute('identity');
        if (!$identity) {
            return; // 未認証は Authentication が redirect
        }

        $roleId = null;
        if (method_exists($identity, 'get')) {
            $roleId = $identity->get('role_id');
        }

        if (!is_string($roleId) || $roleId === '') {
            throw new ForbiddenException('Role is not assigned.');
        }

        $allowed = $this->permissionChecker->can($roleId, [
            'plugin' => $this->normalizer->normalizePlugin($this->request->getParam('plugin')),
            'prefix' => $this->normalizer->normalizePrefix($this->request->getParam('prefix')),
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
        $this->viewBuilder()->setLayout('CakeLte.default');
    }
}
