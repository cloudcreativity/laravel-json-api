<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Sites;

use CloudCreativity\JsonApi\Schema\ExtractsAttributesTrait;
use CloudCreativity\JsonApi\Utils\Str;
use CloudCreativity\LaravelJsonApi\Tests\Entities\Site;
use InvalidArgumentException;
use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{

    use ExtractsAttributesTrait;

    /**
     * @var string
     */
    protected $resourceType = 'sites';

    /**
     * @var array
     */
    protected $attributes = [
        'domain',
        'name',
    ];

    /**
     * @param object $resource
     * @return mixed
     */
    public function getId($resource)
    {
        if (!$resource instanceof Site) {
            throw new InvalidArgumentException('Expecting a site object.');
        }

        return $resource->getSlug();
    }

    /**
     * @param $record
     * @param $recordKey
     * @return mixed
     */
    protected function extractAttribute($record, $recordKey)
    {
        $method = 'get' . Str::classify($recordKey);

        return call_user_func([$record, $method]);
    }

}

