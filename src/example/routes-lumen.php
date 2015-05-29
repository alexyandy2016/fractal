<?php

$app->group(['prefix' => 'api/v1'], function($app) {
    $app->get('resource',[
        'as' => 'resource.index',
        'uses' => \Appkr\Fractal\Example\ResourceControllerForLumen::class . '@index'
    ]);
    $app->get('show',[
        'as' => 'resource.show',
        'uses' => \Appkr\Fractal\Example\ResourceControllerForLumen::class . '@show'
    ]);
    $app->post('resource',[
        'as' => 'resource.store',
        'uses' => \Appkr\Fractal\Example\ResourceControllerForLumen::class . '@store'
    ]);
    $app->patch('resource',[
        'as' => 'resource.update',
        'uses' => \Appkr\Fractal\Example\ResourceControllerForLumen::class . '@update'
    ]);
    $app->delete('resource',[
        'as' => 'resource.destroy',
        'uses' => \Appkr\Fractal\Example\ResourceControllerForLumen::class . '@destroy'
    ]);
});
