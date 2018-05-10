# Laravel中间件-Response缓存

[![Latest Stable Version](https://poser.pugx.org/flc/laravel-middleware-cache-response/v/stable)](https://packagist.org/packages/flc/laravel-middleware-cache-response)
[![Total Downloads](https://poser.pugx.org/flc/laravel-middleware-cache-response/downloads)](https://packagist.org/packages/flc/laravel-middleware-cache-response)
[![License](https://poser.pugx.org/flc/laravel-middleware-cache-response/license)](https://packagist.org/packages/flc/laravel-middleware-cache-response)

## 功能

- 支持缓存渲染后数据
- 支持指定缓存过期时间（默认10分钟）
- header头输出缓存命中状态、缓存Key及过期时间

## 安装

```sh
composer require flc/laravel-middleware-cache-response
```

## 配置

> `\app\Http\Kernel.php`文件中`$routeMiddleware`增加：

```php
<?php
'cache.response' => \Flc\Laravel\Http\Middleware\CacheResponse::class,

// cache.response 命名随意，你开心就好
```

## 使用

```php
<?php
Route::get('/', function () {
    return view('welcome');
})->middleware('cache.response');

Route::get('/', function () {
    return view('welcome');
})->middleware('cache.response:20');  // 指定缓存时间20分钟
```

## 附录

**缓存规则**

- 当前URL全路径md5

**Headers**

```
X-Cache:Missed
X-Cache-Expires:2018-03-29 15:08:29 CST
X-Cache-Key:6c9b19774e2c304a42d200f314d8c80b
```

## TODO

- 增加`status`、`header`的支持

## License

MIT
