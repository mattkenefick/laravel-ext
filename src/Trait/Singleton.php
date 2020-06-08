<?php namespace PolymerMallard\Trait;

trait Singleton {

    protected static $instance = null;

    /**
     * [instance description]
     * @return [type]
     */
    public static function instance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * [__clone description]
     * @return [type]
     */
    protected function __clone() {

    }

    /**
     * [__construct description]
     */
    protected function __construct() {

    }

}