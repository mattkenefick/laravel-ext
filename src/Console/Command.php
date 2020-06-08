<?php

namespace PolymerMallard\Console;

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;


class Command extends \Illuminate\Console\Command {

    const INDENT_NONE = 0;
    const INDENT_ONE = 1;
    const INDENT_TWO = 2;
    const INDENT_THREE = 3;

    //
    public function __construct($arguments = array())
    {
        parent::__construct();

        $this->input = new Input\MockInput($arguments);
    }

    public function log($string, $indentation = 0)
    {
        $string = (str_repeat("    ", $indentation)) . $string;

        return \Log::info("[$this->name] $string");
    }

    public function info($a)
    {
        if (\App::runningInConsole()) {
            echo "$a\n";

            $this->log($a);
        }
        else {
            // parent::info($a);
        }

        return false;
    }

    /**
     * log
     *
     * Test echo log method that can be overriden
     */
    public function debug($string, $indentation = 0)
    {
        // send to laravel file
        $this->log($string, $indentation);

        $log       = new Logger($this->name);
        $stream    = new StreamHandler($this->getDebugFilepath());
        $formatter = new LineFormatter(null, null, true, true);

        $stream->setFormatter($formatter);
        $log->pushHandler($stream, Logger::DEBUG);

        $string = (str_repeat("    ", $indentation)) . $string;
        $log->addDebug($string);

        return false;
    }

    /**
     * log
     *
     * Test echo log method that can be overriden
     */
    public function error($string, $indentation = 0)
    {
        // send to laravel file
        $this->log($string, $indentation);

        // also run debug, it's annoying to have multiple files...
        $this->debug($string, $indentation);

        $log       = new Logger($this->name);
        $stream    = new StreamHandler($this->getErrorFilepath());
        $formatter = new LineFormatter(null, null, true, true);

        $stream->setFormatter($formatter);
        $log->pushHandler($stream, Logger::ERROR);

        $string = (str_repeat("    ", $indentation)) . $string;
        $log->addError($string);

        return false;
    }


    // Timer
    // -------------------------------------------------

    public function timerStart() {
        $this->_timeStart = microtime(true);
    }

    public function timerEnd() {
        // ignore if we haven't started a timer
        if (!is_numeric($this->_timeStart)) {
            return false;
        }

        $time_end         = microtime(true);
        $execution_time   = ($time_end - $this->_timeStart) / 60;

        // reset timer
        $this->_timeStart = null;

        //execution time of the script
        return $execution_time;
    }


    // Helpers
    // -------------------------------------------------

    protected function getErrorFilepath()
    {
        $filename = 'error.' . str_replace('\\', '_', get_called_class()) . '.log';
        $path = storage_path() . '/logs/';

        return $path . $filename;
    }

    protected function getDebugFilepath()
    {
        $filename = 'debug.' . str_replace('\\', '_', get_called_class()) . '.log';
        $path = storage_path() . '/logs/';

        return $path . $filename;
    }

}
