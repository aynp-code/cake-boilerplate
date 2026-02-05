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
        $userId = null;
        $roleId = null;

        if ($identity) {
            // getIdentifier() は通常 id（主キー）を返す
            $userId = $identity->getIdentifier();

            // role_id は identity が持つ属性から取得（Identityの実装に合わせる）
            if (method_exists($identity, 'get')) {
                $roleId = $identity->get('role_id');
            }
        }

        // id(uuid)をどこからでも読めるように置く（リクエスト中限定）
        Configure::write('Auth.User.id', $userId);
        Configure::write('Auth.User.role_id', $roleId);

        try {
            return $handler->handle($request);
        } finally {
            // 念のため掃除（長寿命プロセス対策）
            Configure::delete('Auth.User.id');
            Configure::delete('Auth.User.role_id');
        }
    }
}
