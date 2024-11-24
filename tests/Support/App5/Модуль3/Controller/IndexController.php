<?php

declare(strict_types=1);

namespace Yiisoft\FileRouter\Tests\Support\App5\Модуль3\Controller;

use HttpSoft\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    public function index(): ResponseInterface
    {
        return new TextResponse('Hello, модуль3!', 200);
    }
}
