<?php

declare(strict_types=1);

namespace Yiisoft\FileRouter\Tests\Support\App3\Controller;

use HttpSoft\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;

class UserController
{
    public function index(): ResponseInterface
    {
        return new TextResponse('Hello, index Controller/UserController!');
    }

    public function delete(): ResponseInterface
    {
        return new TextResponse('Hello, delete Controller/UserController!');
    }
}
