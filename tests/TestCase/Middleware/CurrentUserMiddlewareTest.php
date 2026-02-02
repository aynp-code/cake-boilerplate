<?php
declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use App\Middleware\CurrentUserMiddleware;
use Cake\TestSuite\TestCase;

/**
 * App\Middleware\CurrentUserMiddleware Test Case
 */
class CurrentUserMiddlewareTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Middleware\CurrentUserMiddleware
     */
    protected $CurrentUser;

    /**
     * Test process method
     *
     * @return void
     * @link \App\Middleware\CurrentUserMiddleware::process()
     */
    public function testProcess(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
