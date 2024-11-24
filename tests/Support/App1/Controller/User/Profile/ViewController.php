<?php

declare(strict_types=1);

namespace Yiisoft\FileRouter\Tests\Support\App1\Controller\User\Profile;

use HttpSoft\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;

final class ViewController
{
    public function index(): ResponseInterface
    {
        return new TextResponse('Hello, User\Profile\IndexController!', 200);
    }
}
