<?php
namespace luoyy\Blade;

class Autoloader
{
    /**
     * Registers luoyy\Blade\Autoloader as an SPL autoloader and require helpers function.
     */
    public static function register()
    {
        require 'helpers.php';

        spl_autoload_register([self::class, 'autoload']);
    }

    /**
     * Handles autoloading of classes.
     *
     * @param string $class a class name
     */
    public static function autoload($class)
    {
        if (0 !== strpos($class, __NAMESPACE__)) {
            return;
        }
        // psr-4
        if (is_file($file = strtr($class, ['luoyy\\Blade\\' => __DIR__ . DIRECTORY_SEPARATOR]) . '.php')) {
            require $file;
        }
    }
}
