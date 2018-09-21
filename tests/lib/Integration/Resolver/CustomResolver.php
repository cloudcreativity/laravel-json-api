<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Resolver;

use CloudCreativity\LaravelJsonApi\Resolver\AbstractResolver;

class CustomResolver extends AbstractResolver
{

    /**
     * @inheritdoc
     */
    protected function resolve($unit, $resourceType)
    {
        $units = str_plural(strtolower($unit));

        return "{$units}:{$resourceType}";
    }

}
