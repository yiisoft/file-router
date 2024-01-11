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
use Yiisoft\FileRouter\Tests\Support\App4;
use Yiisoft\FileRouter\Tests\Support\App5;
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

    public function testDefaultControllerName(): void
    {
        $router = $this->createRouter();
        $router = $router
            ->withNamespace('Yiisoft\FileRouter\Tests\Support\App2')
            ->withBaseControllerDirectory('Action')
            ->withClassPostfix('Action')
            ->withDefaultControllerName('User');

        $handler = $this->createExceptionHandler();
        $request = new ServerRequest(
            method: 'GET',
            uri: '/',
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

    #[DataProvider('dataRoutesCollision')]
    public function testRoutesCollision(string $method, string $uri, string $expectedResponse): void
    {
        $router = $this->createRouter();
        $router = $router->withNamespace('Yiisoft\FileRouter\Tests\Support\App3');

        $handler = $this->createExceptionHandler();
        $request = new ServerRequest(
            method: $method,
            uri: $uri,
        );

        $response = $router->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expectedResponse, (string) $response->getBody());
    }

    public static function dataRoutesCollision(): iterable
    {
        yield 'direct' => [
            'GET',
            '/user',
            'Hello, Controller/UserController!',
        ];

        yield 'indirect' => [
            'POST',
            '/user',
            'Hello, Controller/User/IndexController!',
        ];
    }

    #[DataProvider('dataUnicodeRoutes')]
    public function testTestUnicodeRoutes(string $method, string $uri, string $expectedResponse): void
    {
        $router = $this->createRouter();
        $router = $router
            ->withNamespace('Yiisoft\FileRouter\Tests\Support\App4')
            ->withClassPostfix('Контроллер')
            ->withBaseControllerDirectory('Контроллеры');

        $handler = $this->createExceptionHandler();
        $request = new ServerRequest(
            method: $method,
            uri: $uri,
        );

        $response = $router->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expectedResponse, (string) $response->getBody());
    }

    public static function dataUnicodeRoutes(): iterable
    {
        yield 'direct' => [
            'GET',
            '/пользователь/главный',
            'Привет, Контроллеры/Пользователь/ГлавныйКонтроллер!',
        ];
    }

    #[DataProvider('dataModularity')]
    public function testModularity(
        string $namespace,
        string $routePrefix,
        string $method,
        string $uri,
        string $expectedResult,
    ): void {
        $router = $this->createRouter();
        $router = $router
            ->withNamespace($namespace)
            ->withRoutePrefix($routePrefix);

        $handler = $this->createExceptionHandler();
        $request = new ServerRequest(
            method: $method,
            uri: $uri,
        );

        $response = $router->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expectedResult, (string) $response->getBody());
    }

    public static function dataModularity(): iterable
    {
        yield 'module1 /' => [
            'Yiisoft\\FileRouter\\Tests\\Support\\App5\\Module1',
            '/module1',
            'GET',
            '/module1',
            'Hello, module1!',
        ];
        yield 'module1 /index' => [
            'Yiisoft\\FileRouter\\Tests\\Support\\App5\\Module1',
            '/module1',
            'GET',
            '/module1/',
            'Hello, module1!',
        ];
        yield 'module2 /index' => [
            'Yiisoft\\FileRouter\\Tests\\Support\\App5\\Module2',
            '/module2',
            'GET',
            '/module2/index',
            'Hello, module2!',
        ];
        yield 'm/o/d/u/l/e /index' => [
            'Yiisoft\\FileRouter\\Tests\\Support\\App5\\Module2',
            '/m/o/d/u/l/e',
            'GET',
            '/m/o/d/u/l/e/index',
            'Hello, module2!',
        ];
    }

    public function testModularityFastPath(): void
    {
        $router = $this->createRouter();
        $router = $router
            ->withNamespace('Yiisoft\\FileRouter\\Tests\\Support\\App5\\Module1')
            ->withRoutePrefix('/module1');


        $handler = $this->createExceptionHandler();
        $request = new ServerRequest(
            method: 'GET',
            uri: '/module2',
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Not implemented from tests.');
        $router->process($request, $handler);
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

            App4\Контроллеры\Пользователь\ГлавныйКонтроллер::class => new App4\Контроллеры\Пользователь\ГлавныйКонтроллер(
            ),

            App5\Module1\Controller\IndexController::class => new App5\Module1\Controller\IndexController(),
            App5\Module2\Controller\IndexController::class => new App5\Module2\Controller\IndexController(),
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
