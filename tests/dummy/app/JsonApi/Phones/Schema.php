<?php

namespace App\JsonApi\Phones;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractSchema;
use App\Phone;

class Schema extends AbstractSchema
{

    /**
     * @var string
     */
    protected $resourceType = 'phones';

    /**
     * @var array
     */
    protected $attributes = [
        'number',
    ];

    /**
     * @param Phone $resource
     * @param bool $isPrimary
     * @param array $includeRelationships
     * @return array
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        return [
            'user' => [
                self::DATA => isset($includeRelationships['user']) ?
                    $resource->user : $this->createBelongsToIdentity($resource, 'user'),
            ],
        ];
    }
}
