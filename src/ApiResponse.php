<?php

namespace Appkr\Fractal;

trait ApiResponse
{
    /**
     * Get a Response instance
     *
     * @return \Appkr\Fractal\Response
     */
    public function response()
    {
        return app(Response::class);
    }

    /**
     * Get a Response instance
     *
     * @return \Appkr\Fractal\Response
     */
    public function respond()
    {
        return app(Response::class);
    }
}