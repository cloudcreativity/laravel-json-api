<?php

namespace CloudCreativity\JsonApi;

use CloudCreativity\JsonApi\Contracts\Integration\EnvironmentInterface;
use Illuminate\Support\Facades\Facade as BaseFacade;

class Facade extends BaseFacade
{

    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return EnvironmentInterface::class;
    }
}
