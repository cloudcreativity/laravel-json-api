<?php

namespace CloudCreativity\LaravelJsonApi\Hydrator;

use CloudCreativity\JsonApi\Hydrator\AbstractHydrator as BaseHydrator;

abstract class AbstractHydrator extends BaseHydrator
{

    /**
     * @param $key
     * @return string
     */
    protected function methodForRelationship($key)
    {
        return sprintf('hydrate%sRelationship', studly_case($key));
    }

}
