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
    public string $baseControllerDirectory = 'Controller';
    public string $classPostfix = 'Controller';
    public string $namespace = 'App';

    public function __construct(
        private readonly MiddlewareDispatcher $middlewareDispatcher,
    ) {
    }

    public function withBaseControllerDirectory(string $directory): self
    {
        $new = clone $this;
        $new->baseControllerDirectory = $directory;

        return $new;
    }

    public function withClassPostfix(string $postfix): self
    {
        $new = clone $this;
        $new->classPostfix = $postfix;

        return $new;
    }

    public function withNamespace(string $namespace): self
    {
        $new = clone $this;
        $new->namespace = $namespace;

        return $new;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $possibleEntrypoints = $this->parseRequestPath($request);

        foreach ($possibleEntrypoints as $possibleEntrypoint) {
            if (empty($possibleEntrypoint)) {
                continue;
            }

            /**
             * @psalm-var class-string $controllerClass
             * @psalm-var string|null $possibleAction
             */
            [$controllerClass, $possibleAction] = $possibleEntrypoint;
            if (!class_exists($controllerClass)) {
                continue;
            }

            /** @psalm-suppress InvalidPropertyFetch */
            $actions = $controllerClass::$actions ?? [
                'HEAD' => 'head',
                'OPTIONS' => 'options',
                'GET' => 'index',
                'POST' => 'create',
                'PUT' => 'update',
                'DELETE' => 'delete',
            ];
            $action = $possibleAction ?? $actions[$request->getMethod()] ?? null;

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
        $possibleAction = null;
        $path = $request->getUri()->getPath();
        if ($path === '/') {
            $controllerName = 'Index';

            yield [
                $this->cleanClassname(
                    $this->namespace . '\\' . $this->baseControllerDirectory . '\\' . $controllerName . $this->classPostfix
                ),
                $possibleAction,
            ];
            return;
        }

        $controllerName = preg_replace_callback(
            '#(/.)#',
            fn(array $matches) => strtoupper($matches[1]),
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
            $controllerName = 'Index';
        }

        yield [
            $this->cleanClassname(
                $this->namespace . '\\' . $this->baseControllerDirectory . '\\' . $directoryPath . '\\' . $controllerName . $this->classPostfix
            ),
            $possibleAction,
        ];
    }

    protected function cleanClassname(string $className): string|array
    {
        return str_replace(
            ['\\/\\', '\\/', '\\\\'],
            '\\',
            $className,
        );
    }
}
