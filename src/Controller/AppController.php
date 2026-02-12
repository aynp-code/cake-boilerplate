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
    }

    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->setLayout('CakeLte.default');
    }
}
