<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\RolePermissionChecker;
use App\Service\RoutePermissionTargetNormalizer;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;

/**
 * @property \Cake\Http\ServerRequest $request
 * @property \Cake\Http\Response $response
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class AppController extends Controller
{
    protected RolePermissionChecker $permissionChecker;
    protected RoutePermissionTargetNormalizer $normalizer;

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');
    }

    /**
     * Set the layout before rendering.
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event The event object.
     * @return void
     */
    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->setLayout('CakeLte.default');
    }
}
