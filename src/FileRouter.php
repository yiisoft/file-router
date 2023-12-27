<?php

declare(strict_types=1);

namespace Yiisoft\FileRouter;

use PHPUnit\Logging\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Strings\StringHelper;

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
        $controllerClass = $this->parseController($request);
        if ($controllerClass === null) {
            return $handler->handle($request);
        }
        $action = $this->parseAction($request);

        if (!method_exists($controllerClass, $action)) {
            return $handler->handle($request);
        }

        $middlewares = $controllerClass::$middlewares[$action] ?? [];
        $middlewares[] = [$controllerClass, $action];

        $middlewareDispatcher = $this->middlewareDispatcher->withMiddlewares($middlewares);

        return $middlewareDispatcher->dispatch($request, $handler);
    }

    private function parseAction(ServerRequestInterface $request): ?string
    {
        switch ($request->getMethod()) {
            case 'HEAD':
            case 'GET':
                $action = 'index';
                break;
            case 'POST':
                $action = 'create';
                break;
            case 'PUT':
                $action = 'update';
                break;
            case 'DELETE':
                $action = 'delete';
                break;
            default:
                throw new Exception('Not implemented.');
        }
        return $action;
    }

    private function parseController(ServerRequestInterface $request): mixed
    {
        $path = $request->getUri()->getPath();
        if ($path === '/') {
            $controllerName = 'Index';
            $directoryPath = '';
        } else {
            $controllerName = preg_replace_callback(
                '#(/.)#',
                fn(array $matches) => strtoupper($matches[1]),
                str_replace('/', DIRECTORY_SEPARATOR, $path)
            );
            $directoryPath = StringHelper::directoryName($controllerName);

            $controllerName = StringHelper::basename($controllerName);
        }

        $controller = $controllerName . $this->classPostfix;
        $className = str_replace(
            ['/', '\\\\'],
            ['\\', '\\'],
            $this->namespace . '\\' . $this->baseControllerDirectory . '\\' . $directoryPath . '\\' . $controller
        );

        if (class_exists($className)) {
            return $className;
        }

        // alternative version finding namespace by file


        //$controllerDirectory = $this->aliases->get('src') . DIRECTORY_SEPARATOR . $this->baseControllerDirectory;
        //$classPath = $controllerDirectory . DIRECTORY_SEPARATOR . $directoryPath . DIRECTORY_SEPARATOR . $controller;
        //$filename = $classPath . '.php';
        //if (file_exists($filename)) {
        //    $content = file_get_contents($filename);
        //    $namespace = preg_match('#namespace\s+(.+?);#', $content, $matches) ? $matches[1] : '';
        //    if (class_exists($namespace . '\\' . $controller)) {
        //        return $namespace . '\\' . $controller;
        //    }
        //}

        return null;
    }
}
