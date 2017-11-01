# Blade

This is a view templating engine which is extracted from Laravel. It's independent without relying on Laravel's Container or any others.


### Installation

With Composer, you just need to run

``` sh
composer require luoyy/blade
```

If you haven't use composer, you should add all the files in folder `src` to your project folder,
and then `require` them in your code.


### Usage

```php
<?php

require '../src/Autoloader.php';

luoyy\Blade\Autoloader::register();

use luoyy\Blade\FileViewFinder;
use luoyy\Blade\Factory;
use luoyy\Blade\Compilers\BladeCompiler;
use luoyy\Blade\Engines\CompilerEngine;
use luoyy\Blade\Filesystem;
use luoyy\Blade\Engines\EngineResolver;

$path = ['/view_path'];         // your view file path, it's an array
$cachePath = '/cache_path';     // compiled file path

$file = new Filesystem;
$compiler = new BladeCompiler($file, $cachePath);

// you can add a custom directive if you want
$compiler->directive('datetime', function($timestamp) {
    return preg_replace('/(\(\d+\))/', '<?php echo date("Y-m-d H:i:s", $1); ?>', $timestamp);
});

$resolver = new EngineResolver;
$resolver->register('blade', function () use ($compiler) {
    return new CompilerEngine($compiler);
});

// get an instance of factory
$factory = new Factory($resolver, new FileViewFinder($file, $path));

// if your view file extension is not php or blade.php, use this to add it
$factory->addExtension('tpl', 'blade');

// render the template file and echo it
echo $factory->make('hello', ['a' => 1, 'b' => 2])->render();
```

You can enjoy almost all the features of blade with this extension.
However, remember that some of exclusive features are removed.

You can't:

- use `@inject` `@can` `@cannot` `@lang` in a template file
- add any events or middleawares

Documentation: [http://laravel.com/docs/5.5/blade](http://laravel.com/docs/5.5/blade)

Thanks for Laravel and it authors. That is a great project.
