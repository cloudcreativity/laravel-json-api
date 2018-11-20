<?php

namespace CloudCreativity\LaravelJsonApi\Resources;

use CloudCreativity\LaravelJsonApi\Api\AbstractProvider;
use CloudCreativity\LaravelJsonApi\Queue\ClientJob;
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use Illuminate\Contracts\Routing\Registrar;

class ResourceProvider extends AbstractProvider
{

    /**
     * @var array
     */
    protected $resources = [
        'queue-jobs' => ClientJob::class,
    ];

    /**
     * @inheritDoc
     */
    public function mount(ApiGroup $api, Registrar $router)
    {
        // no-op
    }

    /**
     * @inheritDoc
     */
    protected function getRootNamespace()
    {
        return __NAMESPACE__;
    }

}
