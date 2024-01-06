<?php

declare(strict_types=1);

namespace Yiisoft\FileRouter\Tests\Support\App4\Контроллеры\Пользователь;

use HttpSoft\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\FileRouter\Tests\Support\HeaderMiddleware;

class ГлавныйКонтроллер
{
    public static array $actions = [
        'GET' => 'главный',
    ];
    public static array $middlewares = [
        'index' => [
            HeaderMiddleware::class,
        ],
    ];

    public function главный(): ResponseInterface
    {
        return new TextResponse('Привет, Контроллеры/Пользователь/ГлавныйКонтроллер!');
    }
}
