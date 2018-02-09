<?php

namespace CloudCreativity\LaravelJsonApi\Resolver;

use CloudCreativity\JsonApi\Resolver\NamespaceResolver;

class UnitNamespaceResolver extends NamespaceResolver
{

    /**
     * @param string $unit
     * @param string $resourceType
     * @return string
     */
    protected function resolve($unit, $resourceType)
    {
        $unit = str_plural($unit);
        $type = ucfirst(str_singular($resourceType));

        return $this->append(sprintf('%s\%s', $unit, $type));
    }

}
