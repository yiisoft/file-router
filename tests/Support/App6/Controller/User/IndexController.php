<?php

declare(strict_types=1);

namespace Yiisoft\FileRouter\Tests\Support\App6\Controller\User;

use HttpSoft\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    public function create(): ResponseInterface
    {
        return new TextResponse('Hello, create Controller/User/IndexController!');
    }
}
