<?php

namespace CloudCreativity\LaravelJsonApi\Resolver;

/**
 * Class ResolverFactory
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ResolverFactory
{

    /**
     * Create a resolver.
     *
     * @param string $apiName
     * @param array $config
     * @return NamespaceResolver
     */
    public function __invoke($apiName, array $config)
    {
        $byResource = $config['by-resource'];
        $withType = true;

        if ('false-0.x' === $byResource) {
            $byResource = false;
            $withType = false;
        }

        return new NamespaceResolver(
            $config['namespace'],
            (array) $config['resources'],
            (bool) $byResource,
            $withType
        );
    }

}
