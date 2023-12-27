<?php

declare(strict_types=1);

namespace Yiisoft\FileRouter\Tests;

use HttpSoft\Message\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\FileRouter\FileRouter;
use Yiisoft\FileRouter\Tests\Support\App1\Controller\IndexController;
use Yiisoft\FileRouter\Tests\Support\App1\Controller\User\BlogController;
use Yiisoft\FileRouter\Tests\Support\App1\Controller\UserController;
use Yiisoft\FileRouter\Tests\Support\App2\Action\UserAction;
use Yiisoft\FileRouter\Tests\Support\HeaderMiddleware;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class FileRouterTest extends TestCase
{
    public function testMiddleware(): void
    {
        $router = $this->createRouter();
        $router = $router
            ->withNamespace('Yiisoft\FileRouter\Tests\Support\App1')
            ->withBaseControllerDirectory('Controller');

        $handler = $this->createExceptionHandler();
        $request = new ServerRequest(
            method: 'GET',
            uri: '/user/blog',
        );

        $response = $router->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello, index!', (string) $response->getBody());
        $this->assertEquals('x-header-value', $response->getHeaderLine('X-Header-Name'));
    }

    #[DataProvider('dataRouter')]
    public function testRouter(string $method, string $uri, string $expectedResponse): void
    {
        /**
         * @var FileRouter $router
         */
        $router = $this->createRouter();
        $router = $router
            ->withNamespace('Yiisoft\FileRouter\Tests\Support\App1');

        $handler = $this->createExceptionHandler();
        $request = new ServerRequest(
            method: $method,
            uri: $uri,
        );

        $response = $router->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expectedResponse, (string) $response->getBody());
    }

    public static function dataRouter(): iterable
    {
        yield 'GET /user' => [
            'GET',
            '/user',
            'Hello, index!',
        ];
        yield 'POST /user' => [
            'POST',
            '/user',
            'Hello, create!',
        ];
        yield 'PUT /user' => [
            'PUT',
            '/user',
            'Hello, update!',
        ];
        yield 'DELETE /user' => [
            'DELETE',
            '/user',
            'Hello, delete!',
        ];
    }

    public function testUnsupportedMethod(): void
    {
        $router = $this->createRouter();
        $router = $router
            ->withNamespace('Yiisoft\FileRouter\Tests\Support\App1');

        $handler = $this->createExceptionHandler();
        $request = new ServerRequest(
            method: 'DELETE',
            uri: '/',
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Not implemented from tests.');
        $router->process($request, $handler);
    }

    public function testBaseController(): void
    {
        $router = $this->createRouter();
        $router = $router
            ->withNamespace('Yiisoft\FileRouter\Tests\Support\App1');

        $handler = $this->createExceptionHandler();
        $request = new ServerRequest(
            method: 'GET',
            uri: '/',
        );

        $response = $router->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello, index!', (string) $response->getBody());
    }

    public function testUnusualControllerDirectory(): void
    {
        $router = $this->createRouter();
        $router = $router
            ->withNamespace('Yiisoft\FileRouter\Tests\Support\App2')
            ->withBaseControllerDirectory('Action')
            ->withClassPostfix('Action');

        $handler = $this->createExceptionHandler();
        $request = new ServerRequest(
            method: 'GET',
            uri: '/user',
        );

        $response = $router->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello, user action!', (string) $response->getBody());
    }

    private function createRouter(): FileRouter
    {
        $container = new SimpleContainer([
            HeaderMiddleware::class => new HeaderMiddleware(),
            BlogController::class => new BlogController(),
            UserController::class => new UserController(),
            IndexController::class => new IndexController(),
            UserAction::class => new UserAction(),
        ]);

        return new FileRouter(
            new MiddlewareDispatcher(new MiddlewareFactory($container), null)
        );
    }

    private function createExceptionHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \Exception('Not implemented from tests.');
            }
        };
        {
        }
    }
}
