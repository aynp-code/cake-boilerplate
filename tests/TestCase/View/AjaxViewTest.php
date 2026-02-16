<?php
declare(strict_types=1);

namespace App\Test\TestCase\View;

use App\View\AjaxView;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class AjaxViewTest extends TestCase
{
    public function testInitializeSetsAjaxLayoutAndResponseType(): void
    {
        $request = new ServerRequest(['url' => '/']);
        $response = new Response();

        $view = new AjaxView($request, $response);
        $view->initialize();

        $this->assertSame('ajax', $view->getLayout(), 'AjaxView should use ajax layout.');

        $contentType = $view->getResponse()->getHeaderLine('Content-Type');
        $this->assertNotSame('', $contentType, 'Content-Type header should be present after initialize().');
        $this->assertStringContainsString('text/html', $contentType, 'AjaxView should map ajax type to text/html by default.');
    }
}
