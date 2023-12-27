<?php

declare(strict_types=1);

namespace Yiisoft\FileRouter\Tests\Support\App1\Controller\User;

use HttpSoft\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\FileRouter\Tests\Support\HeaderMiddleware;

class BlogController
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
}
