<?php

declare(strict_types=1);

namespace Yiisoft\FileRouter;

use Generator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

/**
 * Provides a convention-based router middleware that chooses controller based on its namespace and class name.
 */
final class FileRouter implements MiddlewareInterface
{
    private string $baseControllerDirectory = 'Controller';
    private string $classPostfix = 'Controller';
    private string $namespace = 'App';
    private string $defaultControllerName = 'Index';
    private string $routePrefix = '';

    public function __construct(
        private readonly MiddlewareDispatcher $middlewareDispatcher,
    ) {
    }

    /**
     * Sets the directory where controllers are located.
     *
     * @see withNamespace() if you want to set the namespace for controller classes.
     */
    public function withBaseControllerDirectory(string $directory): self
    {
        $new = clone $this;
        $new->baseControllerDirectory = $directory;

        return $new;
    }

    /**
     * Sets the postfix for controller class names.
     */
    public function withClassPostfix(string $postfix): self
    {
        $new = clone $this;
        $new->classPostfix = $postfix;

        return $new;
    }

    /**
     * Sets the namespace for controller classes.
     *
     * @see withBaseControllerDirectory() if you want to set the directory where controllers are located.
     */
    public function withNamespace(string $namespace): self
    {
        $new = clone $this;
        $new->namespace = $namespace;

        return $new;
    }

    /**
     * Sets the default controller name.
     */
    public function withDefaultControllerName(string $name): self
    {
        $new = clone $this;
        $new->defaultControllerName = $name;

        return $new;
    }

    /**
     * Sets the route prefix.
     */
    public function withRoutePrefix(string $prefix): self
    {
        $new = clone $this;
        $new->routePrefix = $prefix;

        return $new;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $possibleEntryPoints = $this->parseRequestPath($request);

        foreach ($possibleEntryPoints as $possibleEntryPoint) {
            [$controllerClass, $possibleAction] = $possibleEntryPoint;
            if (!class_exists($controllerClass)) {
                continue;
            }

            /**
             * We believe that `$actions` in controller class, if exists, is a correct array.
             *
             * @var string|null $action
             *
             * @psalm-suppress InvalidPropertyFetch, MixedArrayAccess
             */
            $action = $possibleAction ?? ($controllerClass::$actions ?? [
                'HEAD' => 'head',
                'OPTIONS' => 'options',
                'GET' => 'index',
                'POST' => 'create',
                'PUT' => 'update',
                'PATCH' => 'patch',
                'DELETE' => 'delete',
            ])[$request->getMethod()] ?? null;
            if ($action === null) {
                continue;
            }

            if (!method_exists($controllerClass, $action)) {
                continue;
            }

            /**
             * We believe that `$middlewares` in controller class, if exists, is a correct array.
             *
             * @var array[]|callable[]|string[] $middlewares
             *
             * @psalm-suppress InvalidPropertyFetch, MixedArrayAccess
             */
            $middlewares = $controllerClass::$middlewares[$action] ?? [];
            $middlewares[] = [$controllerClass, $action];

            $middlewareDispatcher = $this->middlewareDispatcher->withMiddlewares($middlewares);

            return $middlewareDispatcher->dispatch($request, $handler);
        }

        return $handler->handle($request);
    }

    /**
     * @psalm-return Generator<list{string,string|null}>
     */
    private function parseRequestPath(ServerRequestInterface $request): Generator
    {
        $path = urldecode($request->getUri()->getPath());

        if ($this->routePrefix !== '') {
            if (!str_starts_with($path, $this->routePrefix)) {
                return;
            }
            $path = mb_substr($path, mb_strlen($this->routePrefix));
            if ($path === '') {
                $path = '/';
            }
        }

        if ($path === '/') {
            yield [
                $this->makeClassName($this->defaultControllerName),
                null,
            ];
            return;
        }

        $controllerName = preg_replace_callback(
            '#/.#u',
            static fn(array $matches) => mb_strtoupper($matches[0]),
            $path,
        );

        if (!preg_match('#^/?(.*?)/([^/]+)/?$#', $controllerName, $matches)) {
            return;
        }

        [$_, $directoryPath, $controllerName] = $matches;

        yield [
            $this->makeClassName($controllerName, $directoryPath),
            null,
        ];

        if ($directoryPath === '') {
            yield [
                $this->makeClassName($this->defaultControllerName, $controllerName),
                null,
            ];
        } else {
            yield [
                $this->makeClassName($directoryPath),
                strtolower($controllerName),
            ];
        }
    }

    private function makeClassName(string $controllerName, string $directoryPath = ''): string
    {
        $parts = [];
        if ($this->namespace !== '') {
            $parts[] = $this->namespace;
        }
        if ($this->baseControllerDirectory !== '') {
            $parts[] = $this->baseControllerDirectory;
        }
        if ($directoryPath !== '') {
            $parts[] = $directoryPath;
        }
        $parts[] = $controllerName . $this->classPostfix;
        return implode('\\', $parts);
    }
}
