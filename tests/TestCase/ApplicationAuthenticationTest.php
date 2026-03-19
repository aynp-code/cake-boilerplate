<?php
declare(strict_types=1);

namespace App\Test\TestCase;

use App\Application;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class ApplicationAuthenticationTest extends TestCase
{
    public function testAuthenticationServiceBuildsWithoutException(): void
    {
        $app = new Application(CONFIG);
        $request = new ServerRequest();

        $service = $app->getAuthenticationService($request);

        $this->assertNotNull($service);
    }
}
