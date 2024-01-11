<?php

declare(strict_types=1);

namespace Yiisoft\FileRouter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

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
        $possibleEntrypoints = $this->parseRequestPath($request);

        foreach ($possibleEntrypoints as $possibleEntrypoint) {
            /**
             * @psalm-var class-string $controllerClass
             * @psalm-var string|null $possibleAction
             */
            [$controllerClass, $possibleAction] = $possibleEntrypoint;
            if (!class_exists($controllerClass)) {
                continue;
            }

            /** @psalm-suppress InvalidPropertyFetch */
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

            /** @psalm-suppress InvalidPropertyFetch */
            $middlewares = $controllerClass::$middlewares[$action] ?? [];
            $middlewares[] = [$controllerClass, $action];

            $middlewareDispatcher = $this->middlewareDispatcher->withMiddlewares($middlewares);

            return $middlewareDispatcher->dispatch($request, $handler);
        }

        return $handler->handle($request);
    }

    private function parseRequestPath(ServerRequestInterface $request): iterable
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

        $possibleAction = null;

        if ($path === '/') {
            yield [
                $this->cleanClassname(
                    $this->namespace . '\\' . $this->baseControllerDirectory . '\\' . $this->defaultControllerName . $this->classPostfix
                ),
                $possibleAction,
            ];
            return;
        }

        $controllerName = preg_replace_callback(
            '#(/.)#u',
            static fn(array $matches) => mb_strtoupper($matches[1]),
            $path,
        );

        if (!preg_match('#^(.*?)/([^/]+)/?$#', $controllerName, $matches)) {
            return;
        }

        $directoryPath = $matches[1];
        $controllerName = $matches[2];

        yield [
            $this->cleanClassname(
                $this->namespace . '\\' . $this->baseControllerDirectory . '\\' . $directoryPath . '\\' . $controllerName . $this->classPostfix
            ),
            $possibleAction,
        ];

        if (preg_match('#^(.*?)/([^/]+)/?$#', $directoryPath, $matches)) {
            $possibleAction = strtolower($controllerName);
            $directoryPath = $matches[1];
            $controllerName = $matches[2];
        } else {
            $directoryPath = $controllerName;
            $controllerName = $this->defaultControllerName;
        }

        yield [
            $this->cleanClassname(
                $this->namespace . '\\' . $this->baseControllerDirectory . '\\' . $directoryPath . '\\' . $controllerName . $this->classPostfix
            ),
            $possibleAction,
        ];
    }

    private function cleanClassname(string $className): string
    {
        return str_replace(
            ['\\/\\', '\\/', '\\\\'],
            '\\',
            $className,
        );
    }
}
