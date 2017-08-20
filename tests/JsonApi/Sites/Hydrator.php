<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Sites;

use CloudCreativity\JsonApi\Hydrator\AbstractHydrator;
use CloudCreativity\JsonApi\Hydrator\HydratesAttributesTrait;
use CloudCreativity\JsonApi\Utils\Str;

class Hydrator extends AbstractHydrator
{

    use HydratesAttributesTrait;

    /**
     * @var array
     */
    protected $attributes = [
        'domain',
        'name',
    ];

    /**
     * @param object $record
     * @param string $attrKey
     * @param mixed $value
     * @return void
     */
    protected function hydrateAttribute($record, $attrKey, $value)
    {
        $method = 'set' . Str::classify($attrKey);

        call_user_func([$record, $method], $value);
    }

}
