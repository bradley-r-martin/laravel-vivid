<?php

namespace BRM\Vivid;

use Illuminate\Support\ServiceProvider;
use \BRM\Vivid\app\Transformers\ApiTransformer;

class FrameworkServiceProvider extends ServiceProvider
{
    public function boot(\Illuminate\Routing\Router $router, \Illuminate\Contracts\Http\Kernel $kernel)
    {
        \Response::macro('api', function ($response, $status) {
            return ApiTransformer::response($response, $status);
        });
    }
}
