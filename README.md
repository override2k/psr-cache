[![Build Status](https://travis-ci.org/override2k/psr-cache.svg?branch=master)](https://travis-ci.org/override2k/psr-cache) 
[![codecov](https://codecov.io/gh/override2k/psr-cache/branch/master/graph/badge.svg)](https://codecov.io/gh/override2k/psr-cache)



# PSR-6 Compilant File Cache

This is a basic [PSR-6](http://www.php-fig.org/psr/psr-6/) implementation of cache using the filesystem

## Installation

Install using composer

```sh
composer require overdesign/psr-cache
```

## Usage

Basic example

```php
<?php
use Overdesign\PsrCache\FileCacheDriver;

$cacheDir = __DIR__ . '/cache';

$cache = new FileCacheDriver($cacheDir);

$item = $cache->getItem('myItem');

if ($item->isHit()) {
    echo 'Item found in cache';
    var_dump($item->get());
} else {
    $item->set('my data');
    $item->expiresAfter(120); // Expire in 2 min
    
    $cache->save($item);
}
```

## TODO

- Add [PSR-16](http://www.php-fig.org/psr/psr-16/) support
