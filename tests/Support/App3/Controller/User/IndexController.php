<?php

declare(strict_types=1);

namespace Yiisoft\FileRouter\Tests\Support\App3\Controller\User;

use HttpSoft\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    public function index(): ResponseInterface
    {
        return new TextResponse('Hello, Controller/User/IndexController!');
    }
}
