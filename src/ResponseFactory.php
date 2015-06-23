<?php 

namespace Appkr\Fractal;

class ResponseFactory 
{
    public function make()
    {
        if (is_lumen()) {
            return new \Laravel\Lumen\Http\ResponseFactory();
        }

        return app(\Illuminate\Contracts\Routing\ResponseFactory::class);
    }
}