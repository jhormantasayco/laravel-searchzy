<?php

namespace Jhormantasayco\LaravelSearchzy;

use Illuminate\Support\ServiceProvider;

class LaravelSearchzyServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {

        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-searchzy');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-searchzy');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'searchzy');

        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__.'/../config/searchzy.php' => config_path('searchzy.php'),
            ], 'config');

            // Publishing the views.
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/searchzy'),
            ], 'views');

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/laravel-searchzy'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/laravel-searchzy'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {

        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/searchzy.php', 'searchzy');
    }
}
