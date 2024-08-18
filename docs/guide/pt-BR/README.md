# Yii File Router

> **Notas:**
>
>- O roteador pode ser usado junto com o pacote [`yiisoft/router`](https://github.com/yiisoft/router).
>- Assim que o roteador encontrar uma rota correspondente, ele interromperá a fila de middleware e executará a ação do controlador.

## Uso geral

1. Adicione `\Yiisoft\FileRouter\FileRouter` à lista de middlewares na configuração da sua aplicação, `web/params.php`:

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

2. [Configure o roteador](#configuração) de acordo com suas necessidades.

## Configuração

`\Yiisoft\FileRouter\FileRouter` suporta as seguintes opções de configuração:

### `withBaseControllerDirectory(string $directory): self`

Define o diretório onde os controladores estão localizados.

Por padrão, está definido como `Controller`.

### `withClassPostfix(string $postfix): self`

Define o postfix para nomes de classes de controlador.

Por padrão, está definido como `Controller`.

### `withNamespace(string $namespace): self`

Define o namespace para classes de controlador.

Por padrão, está definido como `App`.

### `withDefaultControllerName(string $name): self`

Define o nome do controlador padrão.

Por padrão, está definido como `Index`.

### `withRoutePrefix(string $prefix): self`

Define o prefixo da rota.

Por padrão é vazio.

Pode ser útil se você quiser adicionar um prefixo a todas as rotas ou separar rotas de diferentes [módulos](#modularidade).

## Middlewares

`\Yiisoft\FileRouter\FileRouter` suporta a adição de middlewares às rotas.

Para adicionar um middleware, adicione a propriedade estática `$middlewares` à classe do controlador:

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

Onde `index` é o nome do método e o valor é uma matriz de nomes de classes de middleware ou definições de middleware.

Veja todos os formatos de definições de middleware suportados no
pacote [Middleware Dispatcher](https://github.com/yiisoft/middleware-dispatcher#general-usage).

## Matching

### Métodos HTTP correspondentes

O roteador mapeia métodos HTTP para métodos de ação do controlador da seguinte forma:

| Método    | Ação        |
|-----------|-------------|
| `HEAD`    | `head()`    |
| `OPTIONS` | `options()` |
| `GET`     | `index()`   |
| `POST`    | `create()`  |
| `PUT`     | `update()`  |
| `PATCH`   | `patch()`   |
| `DELETE`  | `delete()`  |

> Nota: Se o controlador não tiver um método que corresponda ao método HTTP, o roteador **não** lançará uma exceção.

### Rotas personalizadas

Para adicionar uma rota personalizada, adicione a propriedade estática `$actions` à classe do controlador:

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

> Nota: Depois de substituir o mapa de ações, o roteador procurará apenas as ações especificadas no mapa.
> No exemplo acima, o roteador não conseguirá encontrar as ações `index` / `delete`, etc.

### Colisão de rota

Vamos imaginar que você tenha uma solicitação `GET /user/index`.

Possui duas variações possíveis de controlador e ação:

- `src/Controller/User/IndexController::index()`
   - A classe `User/IndexController` corresponde ao caminho completo da rota (`user/index`)
   - O método `index()` foi correspondido por [HTTP methods matching](#http-methods-matching)
- `src/Controller/UserController::index()`
   - A classe `UserController` correspondeu à primeira parte do caminho da rota (`user`)
   - O método `index()` correspondeu à segunda parte do caminho da rota (`index`)

Por exemplo, se você tiver um `UserController` e um `User/IndexController`, uma solicitação `GET` para `/user` será tratada
pelo `UserController`, se tiver um método `index()`.

Caso contrário, `User/IndexController` será usado junto com [HTTP methods matching](#http-methods-matching).

## Rotas Unicode

O roteador também oferece suporte a Unicode em rotas, nomes de controladores e nomes de ações.

Você pode usar caracteres Unicode em seus URIs, nomes de classes de controladores e nomes de métodos de ação.

## Modularidade

Você pode adicionar mais de um roteador ao aplicativo. Pode ajudar a construir um aplicativo modular.

Por exemplo, você tem dois módulos com o mesmo nome de controlador:

```text
- src
  - Module1/Controller/
    - UserController.php
  - Module2/Controller/
    - UserController.php
```

Para adicionar o roteador para cada módulo, adicione o seguinte código à configuração do aplicativo:

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

Como um middleware normal, cada roteador será executado um por um. O primeiro roteador que corresponder à rota será usado.
