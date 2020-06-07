<?php namespace PolymerMallard\Provider;

use PolymerMallard\Exception\Handler;
use PolymerMallard\Event\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;


class EventServiceProvider extends ServiceProvider
{

    /**
     * Register the application's event listeners.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        $this->app->bindShared('api.exception', function ($app) {
            return new Handler;
        });
    }

    /**
     * Register bindings for the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Illuminate\Contracts\Debug\ExceptionHandler', function($app)
        // $this->app->singleton('PolymerMallard\Event\ExceptionHandler', function($app)
        {
            return new \PolymerMallard\Event\ExceptionHandler($app['api.exception']);
        });
    }

}
