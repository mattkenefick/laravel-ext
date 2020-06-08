<?php

namespace PolymerMallard\Support\Facades;

/**
 * @see \PolymerMallard\Console\Log
 */
class Log extends \Illuminate\Support\Facades\Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return '\PolymerMallard\Console\Log';
    }

}
