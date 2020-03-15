<?php
include __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';
use luoyy\Blade\Compilers\BladeCompiler;
use luoyy\Blade\Engines\CompilerEngine;
use luoyy\Blade\Engines\EngineResolver;
use luoyy\Blade\Engines\FileEngine;
use luoyy\Blade\Engines\PhpEngine;
use luoyy\Blade\Factory;
use luoyy\Blade\Filesystem;
use luoyy\Blade\FileViewFinder;

$path = [__DIR__]; // your view file path, it's an array
$cachePath = __DIR__ . '/cache_path'; // compiled file path

$file = new Filesystem;
$compiler = new BladeCompiler($file, $cachePath);

// you can add a custom directive if you want
$compiler->directive('datetime', function ($timestamp) {
    return preg_replace('/(\(\d+\))/', '<?php echo date("Y-m-d H:i:s", $1); ?>', $timestamp);
});

$resolver = new EngineResolver;
$resolver->register('file', function () {
    return new FileEngine;
});
$resolver->register('php', function () {
    return new PhpEngine;
});
$resolver->register('blade', function () use ($compiler) {
    return new CompilerEngine($compiler);
});
$finder = new FileViewFinder($file, $path);

// get an instance of factory
$factory = new Factory($resolver, $finder);
// if your view file extension is not php or blade.php, use this to add it
$factory->addExtension('tpl', 'blade');

// render the template file and echo it
echo $factory->make('hello', ['a' => 1, 'b' => 2])->render();
