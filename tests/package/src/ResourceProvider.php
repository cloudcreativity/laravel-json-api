<?php

namespace Package;

use CloudCreativity\LaravelJsonApi\Api\ResourceProvider as BaseResourceProvider;
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use Illuminate\Contracts\Routing\Registrar;

class ResourceProvider extends BaseResourceProvider
{

    /**
     * @var array
     */
    protected $resources = [
        'blogs' => Blog::class,
    ];

    /**
     * @inheritDoc
     */
    public function mount(ApiGroup $api, Registrar $router)
    {
        $api->resource('blogs', [
            'controller' => '\\' . Http\Controllers\BlogsController::class,
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function getRootNamespace()
    {
        return __NAMESPACE__ . '\\Resources';
    }

}
