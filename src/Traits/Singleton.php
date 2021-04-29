<?php

namespace PolymerMallard\Traits;

trait Singleton {

    /**
     * Reference to instance
     *
     * @var self
     */
    protected static $instance = null;

    /**
     * Get instance method
     *
     * @return self
     */
    public static function instance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

}