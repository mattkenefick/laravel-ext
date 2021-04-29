<?php

namespace PolymerMallard\Data;

class State {
    protected static $properties = [];

    public static function header($key, $value = null) {
        if (isset($value)) {
            self::$properties["header.$key"] = $value;
        }

        return @self::$properties["header.$key"] ?: '';
    }
}
