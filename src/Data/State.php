<?php namespace PolymerMallard\Data;

use PolymerMallard\Exception\ApiException;
use League\Fractal;

class State
{

    protected static $properties = array();


    public static function header($key, $value = NULL)
    {
        if (isset($value))
            self::$properties["header.$key"] = $value;

        return @self::$properties["header.$key"] ?: '';
    }

}
