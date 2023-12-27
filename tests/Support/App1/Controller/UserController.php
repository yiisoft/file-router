<?php

declare(strict_types=1);

namespace Yiisoft\FileRouter\Tests\Support\App1\Controller;

use HttpSoft\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\FileRouter\Tests\Support\HeaderMiddleware;

class UserController
{
    public static array $middlewares = [
        'index' => [
            HeaderMiddleware::class,
        ],
    ];

    public function index(): ResponseInterface
    {
        return new TextResponse('Hello, index!');
    }

    public function create(): ResponseInterface
    {
        return new TextResponse('Hello, create!');
    }

    public function update(): ResponseInterface
    {
        return new TextResponse('Hello, update!');
    }

    public function delete(): ResponseInterface
    {
        return new TextResponse('Hello, delete!');
    }
}
