<?php

namespace Appkr\Fractal;

trait ApiResponse
{
    /**
     * Get a Response instance
     *
     * @return Response
     */
    public function response()
    {
        return app(Response::class);
    }

    /**
     * Get a Response instance
     *
     * @return Response
     */
    public function respond()
    {
        return app(Response::class);
    }
}