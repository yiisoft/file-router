<?php

declare(strict_types=1);

namespace Yiisoft\FileRouter\Tests\Support;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HeaderMiddleware implements MiddlewareInterface
{
    private const X_HEADER_NAME = 'x-header-name';
    private const X_HEADER_VALUE = 'x-header-value';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response->withHeader(self::X_HEADER_NAME, self::X_HEADER_VALUE);
    }
}
