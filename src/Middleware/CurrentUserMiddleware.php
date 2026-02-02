<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Core\Configure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CurrentUser middleware
 */
class CurrentUserMiddleware implements MiddlewareInterface
{
    /**
     * Process method.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Authentication が付与する identity を取得
        $identity = $request->getAttribute('identity');

        // id(uuid)をどこからでも読めるように置く（リクエスト中限定）
        Configure::write('Auth.User.id', $identity?->getIdentifier());

        try {
            return $handler->handle($request);
        } finally {
            // 念のため掃除（長寿命プロセス対策）
            Configure::delete('Auth.User.id');
        }
    }
}
