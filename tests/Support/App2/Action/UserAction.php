<?php

declare(strict_types=1);

namespace Yiisoft\FileRouter\Tests\Support\App2\Action;

use HttpSoft\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\FileRouter\Tests\Support\HeaderMiddleware;

class UserAction
{
    public static array $middlewares = [
        'index' => [
            HeaderMiddleware::class,
        ],
    ];

    public function index(): ResponseInterface
    {
        return new TextResponse('Hello, user/index action!');
    }

    public function hello(): ResponseInterface
    {
        return new TextResponse('Hello, user/hello action!');
    }
}
