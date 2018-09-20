<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Resolver;

use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use DummyApp\Post;

class Resolver implements ResolverInterface
{
    /**
     * @inheritDoc
     */
    public function isType($type)
    {
        return Post::class === $type;
    }

    /**
     * @inheritDoc
     */
    public function getType($resourceType)
    {
        if (!$this->isResourceType($resourceType)) {
            throw new \RuntimeException('Unexpected resource type.');
        }

        return Post::class;
    }

    /**
     * @inheritDoc
     */
    public function getAllTypes()
    {
        return [Post::class];
    }

    /**
     * @inheritDoc
     */
    public function isResourceType($resourceType)
    {
        return 'foobars' === $resourceType;
    }

    /**
     * @inheritDoc
     */
    public function getResourceType($type)
    {
        if (!$this->isType($type)) {
            throw new \RuntimeException('Unexpected type.');
        }

        return 'foobars';
    }

    /**
     * @inheritDoc
     */
    public function getAllResourceTypes()
    {
        return ['foobars'];
    }

    /**
     * @inheritDoc
     */
    public function getSchemaByType($type)
    {
        return "schemas:foobars";
    }

    /**
     * @inheritDoc
     */
    public function getSchemaByResourceType($resourceType)
    {
        return "schemas:foobars";
    }

    /**
     * @inheritDoc
     */
    public function getAdapterByType($type)
    {
        return "adapters:foobars";
    }

    /**
     * @inheritDoc
     */
    public function getAdapterByResourceType($resourceType)
    {
        return "adapters:foobars";
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizerByType($type)
    {
        return 'authorizers:foobars';
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizerByResourceType($resourceType)
    {
        return 'authorizers:foobars';
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizerByName($name)
    {
        return "authorizers:{$name}";
    }

    /**
     * @inheritDoc
     */
    public function getValidatorsByType($type)
    {
        return "validators:foobars";
    }

    /**
     * @inheritDoc
     */
    public function getValidatorsByResourceType($resourceType)
    {
        return "validators:foobars";
    }

}
