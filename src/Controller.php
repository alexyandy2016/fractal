<?php

namespace Appkr\Fractal;

use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use ApiResponse;
}