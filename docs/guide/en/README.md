# Yii File Router

> Note:
> - You can use the router along with the [`yiisoft/router`](https://github.com/yiisoft/router) package.
> - Once the router found a matching route, it will interrupt the middleware queue and execute the controller action.

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
                'withNamespace()' => ['MyApp\\Package1'],
                'withDefaultControllerName()' => ['Default'],
            ],
            // ...
        ]
    ];
    ```

2. [Configure the router](#configuration) for your needs.

## Configuration

`\Yiisoft\FileRouter\FileRouter` supports the following configuration options:

### `withBaseControllerDirectory(string $directory): self`

The method sets the directory where the router locates controllers.
By default, it's `Controller`.

### `withClassPostfix(string $postfix): self`

The method sets the postfix for controller class names.
By default, it's `Controller`.

### `withNamespace(string $namespace): self`

The method sets the namespace for controller classes.
By default, it's `App`.

### `withDefaultControllerName(string $name): self`

The method sets default controller name.
By default, it's `Index`.

### `withRoutePrefix(string $prefix): self`

The method sets the route prefix.
By default, it is empty.

It could be useful if you want to add a prefix to all routes or to separate routes
from different [modules](#modularity).

## Middlewares

`\Yiisoft\FileRouter\FileRouter` supports adding middlewares to the routes.

To add middleware, add the static property `$middlewares` to the controller class:

```php
class UserController
{
    public static array $middlewares = [
        'index' => [
            HeaderMiddleware::class,
        ],
    ];

    public function index(): ResponseInterface
    {
        return new TextResponse('Hello, user/index!');
    }
}
```

Where `index` is the method name and the value is an array of middleware class names
or middleware definitions.

Look at all supported middleware definitions formats in
the [Middleware Dispatcher](https://github.com/yiisoft/middleware-dispatcher#general-usage) package.

## Matching

### HTTP methods matching

The router maps HTTP methods to controller action methods as follows:

| Method    | Action      |
|-----------|-------------|
| `HEAD`    | `head()`    |
| `OPTIONS` | `options()` |
| `GET`     | `index()`   |
| `POST`    | `create()`  |
| `PUT`     | `update()`  |
| `PATCH`   | `patch()`   |
| `DELETE`  | `delete()`  |

> Note: If the controller does not have a method that matches the HTTP method, the router **will not** throw an exception.

### Custom routes

To add a custom route, add the static property `$actions` to the controller class:

```php
class UserController
{
    public static array $actions = [
        'GET' => 'main',
    ];

    public function main(): ResponseInterface
    {
        return new TextResponse('Hello, user/main!', 200);
    }
}
```

> Note: Once you override the action map, the router will only look for the actions specified in the map.
> In the example above, the router will fail to find the `index` / `delete`, etc. actions

### Route collision

Let's imagine that you have a request `GET /user/index`.

It has two possible controller and action variations:

- `src/Controller/User/IndexController::index()`
  - `User/IndexController` class has matched by the full route path (`user/index`)
  - `index()` method has matched by the [HTTP methods matching](#http-methods-matching)
- `src/Controller/UserController::index()`
  - `UserController` class has matched by the first part of the route path (`user`)
  - `index()` method has matched by the second part of the route path (`index`)

For example, if you have a `UserController` and a `User/IndexController`, a `GET` request to `/user` will be handled
by the `UserController`, if it has an `index()` method.

Otherwise, the `User/IndexController` will be used along with the [HTTP methods matching](#http-methods-matching).

## Unicode routes

The router also supports Unicode in routes, controller names, and action names.

You can use Unicode characters in your URIs, controller class names, and action method names.

## Modularity

You can add more than one router to the application. It can help to build a modular application.

For example, you have two modules with the same controller name:

```text
- src
  - Module1/Controller/
    - UserController.php
  - Module2/Controller/
    - UserController.php
```

To add the router for each module, add the following code to the application configuration:

`web/params.php`

```php
return [
    'middlewares' => [
        // ...
        [
            'class' => FileRouter::class,
            'withNamespace()' => ['App\\Module1\\'],
            'withRoutePrefix()' => ['module1'],
        ],
        [
            'class' => FileRouter::class,
            'withNamespace()' => ['App\\Module2\\'],
            'withRoutePrefix()' => ['module2'],
        ],
        // ...
    ]
];
```

Each router is a middleware executed sequentially.
If the first router finds the match, the second one doesn't run.
