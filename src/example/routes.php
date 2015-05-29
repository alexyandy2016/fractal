<?php

Route::group(['prefix' => 'api/v1'], function() {
    Route::resource(
        'resource',
        \Appkr\Fractal\Example\ResourceController::class,
        ['except' => ['create', 'edit']]
    );
});
