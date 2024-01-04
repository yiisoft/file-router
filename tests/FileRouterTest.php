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
use Yiisoft\FileRouter\Tests\Support\App1;
use Yiisoft\FileRouter\Tests\Support\App2;
use Yiisoft\FileRouter\Tests\Support\App3;
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

    public function testTrailingSlash(): void
    {
        /**
         * @var FileRouter $router
         */
        $router = $this->createRouter();
        $router = $router
            ->withNamespace('Yiisoft\FileRouter\Tests\Support\App1');

        $handler = $this->createExceptionHandler();
        $request = new ServerRequest(
            method: 'GET',
            uri: '/user/',
        );

        $response = $router->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello, index!', (string) $response->getBody());
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
            method: 'HEAD',
            uri: '/',
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Not implemented from tests.');
        $router->process($request, $handler);
    }

    public function testNotImplementedAction(): void
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

    public function testUnknownController(): void
    {
        $router = $this->createRouter();
        $router = $router
            ->withNamespace('Yiisoft\FileRouter\Tests\Support\App1');

        $handler = $this->createExceptionHandler();
        $request = new ServerRequest(
            method: 'DELETE',
            uri: '/test/123',
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Not implemented from tests.');
        $router->process($request, $handler);
    }

    public function testIncorrectUrl(): void
    {
        $router = $this->createRouter();
        $router = $router
            ->withNamespace('Yiisoft\FileRouter\Tests\Support\App1');

        $handler = $this->createExceptionHandler();
        $request = new ServerRequest(
            method: 'DELETE',
            uri: '/test//123///',
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
        $this->assertEquals('Hello, user/index action!', (string) $response->getBody());
    }

    public function testCustomRoute(): void
    {
        $router = $this->createRouter();
        $router = $router
            ->withNamespace('Yiisoft\FileRouter\Tests\Support\App2')
            ->withBaseControllerDirectory('Action')
            ->withClassPostfix('Action');

        $handler = $this->createExceptionHandler();
        $request = new ServerRequest(
            method: 'GET',
            uri: '/user/hello',
        );

        $response = $router->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello, user/hello action!', (string) $response->getBody());
    }

    public function testRoutesCollision(): void
    {
        $router = $this->createRouter();
        $router = $router->withNamespace('Yiisoft\FileRouter\Tests\Support\App3');

        $handler = $this->createExceptionHandler();
        $request = new ServerRequest(
            method: 'GET',
            uri: '/user',
        );

        $response = $router->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello, Controller/UserController!', (string) $response->getBody());
    }

    private function createRouter(): FileRouter
    {
        $container = new SimpleContainer([
            HeaderMiddleware::class => new HeaderMiddleware(),

            App1\Controller\User\BlogController::class => new App1\Controller\User\BlogController(),
            App1\Controller\UserController::class => new App1\Controller\UserController(),
            App1\Controller\IndexController::class => new App1\Controller\IndexController(),

            App2\Action\UserAction::class => new App2\Action\UserAction(),

            App3\Controller\UserController::class => new App3\Controller\UserController(),
            App3\Controller\User\IndexController::class => new App3\Controller\User\IndexController(),
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
