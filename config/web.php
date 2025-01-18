<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\FileRouter\FileRouter;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;

return [
    FileRouter::class => static function (ContainerInterface $container) {
        $eventDispatcher = $container->has(EventDispatcherInterface::class)
            ? $container->get(EventDispatcherInterface::class)
            : null;

        $middlewareFactory = $container->get(MiddlewareFactory::class);

        return new FileRouter(new MiddlewareDispatcher($middlewareFactory, $eventDispatcher));
    },
];
