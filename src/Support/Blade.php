<?php
namespace luoyy\Blade\Support;

class Blade
{
    /**
     * All custom "condition" handlers.
     *
     * @var array
     */
    static $conditions = [];

    /**
     * Check the result of a condition.
     *
     * @param  string  $name
     * @param  array  $parameters
     * @return bool
     */
    public static function check($name, ...$parameters)
    {
        return call_user_func(self::$conditions[$name], ...$parameters);
    }
}
