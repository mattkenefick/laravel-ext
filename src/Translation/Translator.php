<?php

namespace PolymerMallard\Translation;

use Illuminate\Translation\Translator as LaravelTranslator;


class Translator extends LaravelTranslator {

    /**
     * Loaded JSON lang file
     */
    private $_jsonLang;

    /**
     * For custom JSON files
     */
    public function json($key = null)
    {
        // get key
        if ($key && isset($this->_jsonLang->$key)) {
            return $this->_jsonLang->$key;
        }

        // get key from fallback
        else if ($key) {
            $fallback = $this->getJson( \Config::get('app.fallback_locale') );

            return $fallback->$key;
        }

        // get all json
        else {
            return $this->_jsonLang;
        }
    }

    public function loadJson($locale = 'en')
    {
        // json
        $this->_jsonLang = $this->getJson($locale);
    }

    protected function getJson($locale = 'en')
    {
        // directory of json files
        $path = base_path() . '/' . \Config::get('app.json_lang_path') . '/' . $locale;

        // lang file
        $file = 'compiled.json';

        // filepath
        $filepath = "$path/$file";

        // load
        $contents = file_get_contents($filepath);

        return json_decode($contents);
    }

}
