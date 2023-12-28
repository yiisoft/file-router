<?php

declare(strict_types=1);

namespace Yiisoft\FileRouter\Tests\Support\App1\Controller;

use HttpSoft\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    public function index(): ResponseInterface
    {
        return new TextResponse('Hello, index!', 200, ['X-Header-Name' => 'X-Header-Value']);
    }
}
