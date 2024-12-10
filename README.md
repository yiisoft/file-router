<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii File Router</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/file-router/v)](https://packagist.org/packages/yiisoft/file-router)
[![Total Downloads](https://poser.pugx.org/yiisoft/file-router/downloads)](https://packagist.org/packages/yiisoft/file-router)
[![Build status](https://github.com/yiisoft/file-router/workflows/build/badge.svg)](https://github.com/yiisoft/file-router/actions?query=workflow%3Abuild)
[![Code Coverage](https://codecov.io/gh/yiisoft/file-router/branch/master/graph/badge.svg)](https://codecov.io/gh/yiisoft/file-router)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Ffile-router%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/file-router/master)
[![static analysis](https://github.com/yiisoft/file-router/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/file-router/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/file-router/coverage.svg)](https://shepherd.dev/github/yiisoft/file-router)
[![psalm-level](https://shepherd.dev/github/yiisoft/file-router/level.svg)](https://shepherd.dev/github/yiisoft/file-router)

The package provides a convention-based router middleware that chooses controller based on its namespace and class name.

## Requirements

- PHP 8.1 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/file-router
```

## General usage

1. Add `\Yiisoft\FileRouter\FileRouter` to the list of middlewares in your application configuration, `web/params.php`:

    ```php
    return [
        'middlewares' => [
            // ...
            \Yiisoft\FileRouter\FileRouter::class,
            // or
            [
                'class' => FileRouter::class,
                'withNamespace()' => ['App'],
                'withBaseControllerDirectory()' => ['Controller'],
            ],
            // ...
        ]
    ];
    ```

2. [Configure the router](docs/guide/en#configuration) for your needs.

By default, the following structure of the app could be used assuming `MyApp\Package1` points to `src` directory:

```
src
  Controller
    User
      Profile
        IndexController.php
      BlogController.php
    UserController.php
    IndexController.php
```

Here's how it works:

- `GET /` → `IndexController::index()`
- `GET /user` → `UserController::index()`
- `POST /user` → `UserController::create()`
- `GET /user/blog/view` → `User/BlogController::view()`
- `GET /user/profile` → `User/Profile/IndexController::index()`


## Documentation

For additional information, check the following docs:

- Guide: [English](docs/guide/en/README.md), [Português - Brasil](docs/guide/pt-BR/README.md)
- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii File Router is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
