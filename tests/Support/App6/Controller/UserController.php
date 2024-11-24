<?php

declare(strict_types=1);

namespace Yiisoft\FileRouter\Tests\Support\App6\Controller;

use HttpSoft\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;

class UserController
{
    public static array $actions = [
        'GET' => 'index',
    ];

    public function index(): ResponseInterface
    {
        return new TextResponse('Hello, index Controller/UserController!');
    }

    public function create(): ResponseInterface
    {
        return new TextResponse('Hello, create Controller/UserController!');
    }
}
