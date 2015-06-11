<?php

namespace Appkr\Fractal;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller extends BaseController
{

    use DispatchesCommands;
    use ValidatesRequests;
    use ApiHelper;

}