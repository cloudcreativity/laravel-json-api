<?php

namespace CloudCreativity\LaravelJsonApi\Factories;

use CloudCreativity\JsonApi\Contracts\Repositories\ErrorRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\JsonApi\Factories\Factory as BaseFactory;
use CloudCreativity\LaravelJsonApi\Api\ResourceProvider;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorErrorFactoryInterface as LaravelValidatorErrorFactory;
use CloudCreativity\LaravelJsonApi\Schema\Container as SchemaContainer;
use CloudCreativity\LaravelJsonApi\Store\Container as AdapterContainer;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorErrorFactory;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidatorFactoryContract;

/**
 * Class Factory
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Factory extends BaseFactory
{

    /**
     * @var Container
     */
    protected $container;

    /**
     * Factory constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function createContainer(array $providers = [])
    {
        $container = new SchemaContainer($this->container, $this, $providers);
        $container->setLogger($this->logger);

        return $container;
    }

    /**
     * @inheritdoc
     */
    public function createAdapterContainer(array $adapters)
    {
        $container = new AdapterContainer($this->container);
        $container->registerMany($adapters);

        return $container;
    }

    /**
     * @param ValidatorErrorFactoryInterface $errors
     * @param StoreInterface $store
     * @return ValidatorFactory
     */
    public function createValidatorFactory(ValidatorErrorFactoryInterface $errors, StoreInterface $store)
    {
        if (!$errors instanceof LaravelValidatorErrorFactory) {
            throw new RuntimeException('Expecting the error factory to be a Laravel-specific error factory.');
        }

        /** @var ValidatorFactoryContract $laravelFactory */
        $laravelFactory = $this->container->make(ValidatorFactoryContract::class);

        return new ValidatorFactory($errors, $store, $laravelFactory);
    }

    /**
     * Return a Laravel-specific validator error factory.
     *
     * @param ErrorRepositoryInterface $errors
     * @return LaravelValidatorErrorFactory
     */
    public function createValidatorErrorFactory(ErrorRepositoryInterface $errors)
    {
        return new ValidatorErrorFactory($errors);
    }

    /**
     * @param $fqn
     * @return ResourceProvider
     */
    public function createResourceProvider($fqn)
    {
        $provider = $this->container->make($fqn);

        if (!$provider instanceof ResourceProvider) {
            throw new RuntimeException("Expecting $fqn to resolve to a resource provider instance.");
        }

        return $provider;
    }
}
