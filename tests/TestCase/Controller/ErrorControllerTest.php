<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ErrorController;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class ErrorControllerTest extends TestCase
{
    public function testBeforeRenderSetsErrorTemplatePath(): void
    {
        $request = new ServerRequest(['url' => '/']);

        $controller = new ErrorController($request);
        $event = new Event('Controller.beforeRender', $controller);

        $controller->beforeRender($event);

        $this->assertSame('Error', $controller->viewBuilder()->getTemplatePath());
    }

    public function testEmptyFilterCallbacksDoNotThrow(): void
    {
        $request = new ServerRequest(['url' => '/']);

        $controller = new ErrorController($request);

        $beforeEvent = new Event('Controller.beforeFilter', $controller);
        $afterEvent = new Event('Controller.afterFilter', $controller);

        $controller->beforeFilter($beforeEvent);
        $controller->afterFilter($afterEvent);

        $this->assertTrue(true);
    }
}
