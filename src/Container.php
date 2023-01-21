<?php
/*
 * Copyright 2022 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CloudCreativity\LaravelJsonApi;

use CloudCreativity\LaravelJsonApi\Contracts\Adapter\ResourceAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Auth\AuthorizerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerAwareInterface;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\ContentNegotiatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Schema\SchemaProviderInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container as IlluminateContainer;

/**
 * Class Container
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Container implements ContainerInterface
{
    /**
     * @var IlluminateContainer
     */
    private IlluminateContainer $container;

    /**
     * @var ResolverInterface
     */
    private ResolverInterface $resolver;

    /**
     * @var array
     */
    private array $createdSchemas = [];

    /**
     * @var array
     */
    private array $createdAdapters = [];

    /**
     * @var array
     */
    private array $createdValidators = [];

    /**
     * @var array
     */
    private array $createdAuthorizers = [];

    /**
     * Container constructor.
     *
     * @param IlluminateContainer $container
     * @param ResolverInterface $resolver
     */
    public function __construct(IlluminateContainer $container, ResolverInterface $resolver)
    {
        $this->container = $container;
        $this->resolver = $resolver;
    }

    /**
     * @inheritDoc
     */
    public function getSchema($resourceObject): SchemaProviderInterface
    {
        return $this->getSchemaByType(get_class($resourceObject));
    }

    /**
     * @inheritDoc
     */
    public function hasSchema(object $resourceObject): bool
    {
        $type = get_class($resourceObject);

        $jsonApiType = $this->resolver->getResourceType($type);

        return !empty($jsonApiType);
    }

    /**
     * Get resource by object type.
     *
     * @param string $type
     * @return SchemaProviderInterface
     */
    public function getSchemaByType(string $type): SchemaProviderInterface
    {
        $resourceType = $this->getResourceType($type);

        return $this->getSchemaByResourceType($resourceType);
    }

    /**
     * Get resource by JSON:API type.
     *
     * @param string $resourceType
     * @return SchemaProviderInterface
     */
    public function getSchemaByResourceType(string $resourceType): SchemaProviderInterface
    {
        if ($this->hasCreatedSchema($resourceType)) {
            return $this->getCreatedSchema($resourceType);
        }

        if (!$this->resolver->isResourceType($resourceType)) {
            throw new RuntimeException("Cannot create a schema because $resourceType is not a valid resource type.");
        }

        $className = $this->resolver->getSchemaByResourceType($resourceType);
        $schema = $this->createSchemaFromClassName($className);
        $this->setCreatedSchema($resourceType, $schema);

        return $schema;
    }

    /**
     * @param $record
     * @return ResourceAdapterInterface|null
     */
    public function getAdapter($record)
    {
        return $this->getAdapterByType(get_class($record));
    }

    /**
     * @inheritDoc
     */
    public function getAdapterByType($type)
    {
        $resourceType = $this->getResourceType($type);

        return $this->getAdapterByResourceType($resourceType);
    }

    /**
     * @inheritDoc
     */
    public function getAdapterByResourceType($resourceType)
    {
        if ($this->hasCreatedAdapter($resourceType)) {
            return $this->getCreatedAdapter($resourceType);
        }

        if (!$this->resolver->isResourceType($resourceType)) {
            $this->setCreatedAdapter($resourceType, null);
            return null;
        }

        $className = $this->resolver->getAdapterByResourceType($resourceType);
        $adapter = $this->createAdapterFromClassName($className);
        $this->setCreatedAdapter($resourceType, $adapter);

        return $adapter;
    }

    /**
     * @inheritDoc
     */
    public function getValidators($record)
    {
        return $this->getValidatorsByType(get_class($record));
    }

    /**
     * @inheritDoc
     */
    public function getValidatorsByType($type)
    {
        $resourceType = $this->getResourceType($type);

        return $this->getValidatorsByResourceType($resourceType);
    }

    /**
     * @inheritDoc
     */
    public function getValidatorsByResourceType($resourceType)
    {
        if ($this->hasCreatedValidators($resourceType)) {
            return $this->getCreatedValidators($resourceType);
        }

        if (!$this->resolver->isResourceType($resourceType)) {
            $this->setCreatedValidators($resourceType, null);
            return null;
        }

        $className = $this->resolver->getValidatorsByResourceType($resourceType);
        $validators = $this->createValidatorsFromClassName($className);
        $this->setCreatedValidators($resourceType, $validators);

        return $validators;
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizer($record)
    {
        return $this->getAuthorizerByType(get_class($record));
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizerByType($type)
    {
        $resourceType = $this->getResourceType($type);

        return $this->getAuthorizerByResourceType($resourceType);
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizerByResourceType($resourceType)
    {
        if ($this->hasCreatedAuthorizer($resourceType)) {
            return $this->getCreatedAuthorizer($resourceType);
        }

        if (!$this->resolver->isResourceType($resourceType)) {
            $this->setCreatedAuthorizer($resourceType, null);
            return null;
        }

        $className = $this->resolver->getAuthorizerByResourceType($resourceType);
        $authorizer = $this->createAuthorizerFromClassName($className);
        $this->setCreatedAuthorizer($resourceType, $authorizer);

        return $authorizer;
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizerByName($name)
    {
        if (!$className = $this->resolver->getAuthorizerByName($name)) {
            throw new RuntimeException("Authorizer [$name] is not recognised.");
        }

        $authorizer = $this->create($className);

        if (!$authorizer instanceof AuthorizerInterface) {
            throw new RuntimeException("Class [$className] is not an authorizer.");
        }

        return $authorizer;
    }

    /**
     * @inheritDoc
     */
    public function getContentNegotiatorByResourceType($resourceType)
    {
        $className = $this->resolver->getContentNegotiatorByResourceType($resourceType);

        return $this->createContentNegotiatorFromClassName($className);
    }

    /**
     * @inheritDoc
     */
    public function getContentNegotiatorByName($name)
    {
        if (!$className = $this->resolver->getContentNegotiatorByName($name)) {
            throw new RuntimeException("Content negotiator [$name] is not recognised.");
        }

        $negotiator = $this->create($className);

        if (!$negotiator instanceof ContentNegotiatorInterface) {
            throw new RuntimeException("Class [$className] is not a content negotiator.");
        }

        return $negotiator;
    }

    /**
     * Get the JSON API resource type for the provided PHP type.
     *
     * @param $type
     * @return null|string
     */
    protected function getResourceType($type)
    {
        if (!$resourceType = $this->resolver->getResourceType($type)) {
            throw new RuntimeException("No JSON API resource type registered for PHP class {$type}.");
        }

        return $resourceType;
    }

    /**
     * @param string $resourceType
     * @return bool
     */
    protected function hasCreatedSchema($resourceType)
    {
        return isset($this->createdSchemas[$resourceType]);
    }

    /**
     * @param string $resourceType
     * @return SchemaProviderInterface
     */
    protected function getCreatedSchema($resourceType): SchemaProviderInterface
    {
        return $this->createdSchemas[$resourceType];
    }

    /**
     * @param string $resourceType
     * @param SchemaProviderInterface $schema
     * @return void
     */
    protected function setCreatedSchema($resourceType, SchemaProviderInterface $schema): void
    {
        $this->createdSchemas[$resourceType] = $schema;
    }

    /**
     * @param string $className
     * @return SchemaProviderInterface
     */
    protected function createSchemaFromClassName(string $className): SchemaProviderInterface
    {
        $schema = $this->create($className);

        if (!$schema instanceof SchemaProviderInterface) {
            throw new RuntimeException("Class [$className] is not a schema provider.");
        }

        return $schema;
    }

    /**
     * @param string $resourceType
     * @return bool
     */
    protected function hasCreatedAdapter($resourceType)
    {
        return array_key_exists($resourceType, $this->createdAdapters);
    }

    /**
     * @param string $resourceType
     * @return ResourceAdapterInterface|null
     */
    protected function getCreatedAdapter($resourceType)
    {
        return $this->createdAdapters[$resourceType];
    }

    /**
     * @param string $resourceType
     * @param ResourceAdapterInterface|null $adapter
     * @return void
     */
    protected function setCreatedAdapter($resourceType, ResourceAdapterInterface $adapter = null)
    {
        $this->createdAdapters[$resourceType] = $adapter;
    }

    /**
     * @param $className
     * @return ResourceAdapterInterface
     */
    protected function createAdapterFromClassName($className)
    {
        $adapter = $this->create($className);

        if (!$adapter instanceof ResourceAdapterInterface) {
            throw new RuntimeException("Class [$className] is not a resource adapter.");
        }

        return $adapter;
    }

    /**
     * @param string $resourceType
     * @return bool
     */
    protected function hasCreatedValidators($resourceType)
    {
        return array_key_exists($resourceType, $this->createdValidators);
    }

    /**
     * @param string $resourceType
     * @return ValidatorFactoryInterface|null
     */
    protected function getCreatedValidators($resourceType)
    {
        return $this->createdValidators[$resourceType];
    }

    /**
     * @param string $resourceType
     * @param ValidatorFactoryInterface|null $validators
     * @return void
     */
    protected function setCreatedValidators($resourceType, $validators = null)
    {
        $this->createdValidators[$resourceType] = $validators;
    }

    /**
     * @param $className
     * @return ValidatorFactoryInterface|null
     */
    protected function createValidatorsFromClassName($className)
    {
        if (!$validators = $this->create($className)) {
            return null;
        }

        if (!$validators instanceof ValidatorFactoryInterface) {
            throw new RuntimeException("Class [$className] is not a resource validator factory.");
        }

        return $validators;
    }

    /**
     * @param string $resourceType
     * @return bool
     */
    protected function hasCreatedAuthorizer($resourceType)
    {
        return array_key_exists($resourceType, $this->createdAuthorizers);
    }

    /**
     * @param string $resourceType
     * @return AuthorizerInterface|null
     */
    protected function getCreatedAuthorizer($resourceType)
    {
        return $this->createdAuthorizers[$resourceType];
    }

    /**
     * @param string $resourceType
     * @param AuthorizerInterface|null $authorizer
     * @return void
     */
    protected function setCreatedAuthorizer($resourceType, AuthorizerInterface $authorizer = null)
    {
        $this->createdAuthorizers[$resourceType] = $authorizer;
    }

    /**
     * @param $className
     * @return AuthorizerInterface|null
     */
    protected function createAuthorizerFromClassName($className)
    {
        $authorizer = $this->create($className);

        if (!is_null($authorizer) && !$authorizer instanceof AuthorizerInterface) {
            throw new RuntimeException("Class [$className] is not a resource authorizer.");
        }

        return $authorizer;
    }

    /**
     * @param $className
     * @return ContentNegotiatorInterface|null
     */
    protected function createContentNegotiatorFromClassName($className)
    {
        $negotiator = $this->create($className);

        if (!is_null($negotiator) && !$negotiator instanceof ContentNegotiatorInterface) {
            throw new RuntimeException("Class [$className] is not a resource content negotiator.");
        }

        return $negotiator;
    }

    /**
     * @param string|null $className
     * @return mixed|nulL
     */
    protected function create(?string $className)
    {
        if (false === $this->exists($className)) {
            return null;
        }

        try {
            $value = $this->container->make($className);
        } catch (BindingResolutionException $ex) {
            throw new RuntimeException(
                sprintf('JSON:API container was unable to build %s via the service container.', $className),
                0,
                $ex,
            );
        }

        if ($value instanceof ContainerAwareInterface) {
            $value->withContainer($this);
        }

        return $value;
    }

    /**
     * @param string|null $className
     * @return bool
     */
    protected function exists(?string $className): bool
    {
        if (null === $className) {
            return false;
        }

        return class_exists($className) || $this->container->bound($className);
    }

}
