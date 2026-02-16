<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\AppController;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class AppControllerTest extends TestCase
{
    public function testInitializeLoadsFlashComponent(): void
    {
        $request = new ServerRequest(['url' => '/']);
        $controller = new AppController($request);

        // In CakePHP, initialize() is invoked by the controller constructor.
        // If this ever changes, calling initialize() twice could trigger duplicate component loading,
        // so we only assert here.
        $this->assertTrue(
            $controller->components()->has('Flash'),
            'AppController should load the Flash component.'
        );
    }
}
