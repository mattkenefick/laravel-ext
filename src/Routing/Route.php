<?php

namespace PolymerMallard\Routing;

class Route {
    /**
     * @return void
     */
    public static function api($endpoint, $controller) {
        // @todo mk: this doesn't seem like the best way to do this
        if (strpos($endpoint, '{code}')) {
            \Route::get("$endpoint", "$controller@getWithCode");
            \Route::get("$endpoint/{id}", "$controller@getWithCode_single");
        } else {
            \Route::get("$endpoint", "$controller@get_index");
            \Route::get("$endpoint/{id}", "$controller@get_single");
        }

        \Route::post("$endpoint", "$controller@post_index");
        \Route::put("$endpoint/{id}", "$controller@put_single");
        \Route::delete("$endpoint/{id}", "$controller@delete_single");
    }
}
