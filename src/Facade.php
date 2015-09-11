<?php

namespace CloudCreativity\JsonApi\Facades;

use Illuminate\Support\Facades\Facade as BaseFacade;
use CloudCreativity\JsonApi\Services\EnvironmentService;

class Facade extends BaseFacade
{

    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return EnvironmentService::class;
    }
}
