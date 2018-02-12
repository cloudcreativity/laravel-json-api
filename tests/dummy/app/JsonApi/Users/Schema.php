<?php

namespace DummyApp\JsonApi\Users;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractSchema;
use DummyApp\User;

class Schema extends AbstractSchema
{

    /**
     * @var string
     */
    protected $resourceType = 'users';

    /**
     * @var array
     */
    protected $attributes = ['name', 'email'];

    /**
     * @param User $resource
     * @param bool $isPrimary
     * @param array $includeRelationships
     * @return array
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        return [
            'phone' => [
                self::DATA => $resource->phone,
            ],
        ];
    }
}
