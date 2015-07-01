<?php

namespace Appkr\Fractal;

use Illuminate\Support\ServiceProvider;
use League\Fractal\Manager as Fractal;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            realpath(__DIR__ . '/../config/fractal.php') => config_path('fractal.php'),
            //realpath(__DIR__ . '/../database/migrations/') => database_path('migrations'),
            //realpath(__DIR__ . '/../database/factories/') => database_path('factories')
        ]);

        $this->mergeConfigFrom(
            realpath(__DIR__ . '/../config/fractal.php'),
            'fractal'
        );

        //include __DIR__.'/./example/routes.php';
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Fractal::class, function ($app) {
            $manager = new Fractal;
            $manager->setSerializer(app($app['config']['fractal']['serializer']));

            return $manager;
        });

        $this->app->alias(Fractal::class, 'api.provider');

        $this->app->bind('api.response', Response::class);
    }
}
