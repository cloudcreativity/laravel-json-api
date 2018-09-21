<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Resolver;

class CreateCustomResolver
{

    /**
     * @param $apiName
     * @param array $config
     * @return CustomResolver
     */
    public function __invoke($apiName, array $config)
    {
        return new CustomResolver((array) $config['resources']);
    }

}
