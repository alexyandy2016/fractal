<?php

$app->group(['prefix' => 'v1'], function ($app) {
    $app->get('resource', [
        'as'   => 'v1.things.index',
        'uses' => \Appkr\Fractal\Example\ResourceControllerForLumen::class . '@index'
    ]);
    $app->get('resource/{id}', [
        'as'   => 'v1.things.show',
        'uses' => \Appkr\Fractal\Example\ResourceControllerForLumen::class . '@show'
    ]);
    $app->post('resource', [
        'as'   => 'v1.things.store',
        'uses' => \Appkr\Fractal\Example\ResourceControllerForLumen::class . '@store'
    ]);
    $app->put('resource/{id}', [
        'as'   => 'v1.things.update',
        'uses' => \Appkr\Fractal\Example\ResourceControllerForLumen::class . '@update'
    ]);
    $app->delete('resource/{id}', [
        'as'   => 'v1.things.destroy',
        'uses' => \Appkr\Fractal\Example\ResourceControllerForLumen::class . '@destroy'
    ]);
});
