<?php

namespace Appkr\Fractal;

use Illuminate\Support\ServiceProvider;
use League\Fractal\Manager as Fractal;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerMigration();
        $this->registerRoute();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //$this->registerHelper();

        $this->app->singleton(Fractal::class, function ($app) {
            $manager = new Fractal;
            $manager->setSerializer(app($app['config']['fractal']['serializer']));

            return $manager;
        });

        $this->app->alias(Fractal::class, 'api.provider');

        $this->app->bind('api.response', Response::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['api.provider', 'api.response'];
    }

    /**
     * Register helper functions
     */
    //protected function registerHelper()
    //{
    //    include __DIR__ . '/./helpers.php';
    //}

    /**
     * Register config
     */
    protected function registerConfig()
    {
        $this->publishes([
            realpath(__DIR__ . '/../config/fractal.php') => config_path('fractal.php'),
        ]);

        $this->mergeConfigFrom(
            realpath(__DIR__ . '/../config/fractal.php'),
            'fractal'
        );
    }

    /**
     * Register migrations
     */
    protected function registerMigration()
    {
        $this->publishes([
            realpath(__DIR__ . '/../database/migrations/') => database_path('migrations'),
            realpath(__DIR__ . '/../database/factories/')  => database_path('factories')
        ]);
    }

    /**
     * Register routes
     */
    protected function registerRoute()
    {
        if (is_lumen()) {
            $app = $this->app;
            include __DIR__ . '/./example/routes-lumen.php';
            return;
        }

        include __DIR__ . '/./example/routes.php';
    }
}
