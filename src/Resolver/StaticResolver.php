<?php

namespace CloudCreativity\LaravelJsonApi\Resolver;

class StaticResolver extends AbstractResolver
{

    /**
     * @var array
     */
    private $adapter;

    /**
     * @var array
     */
    private $authorizer;

    /**
     * @var array
     */
    private $schema;

    /**
     * @var array
     */
    private $validator;

    /**
     * StaticResolver constructor.
     *
     * @param array $resources
     */
    public function __construct(array $resources)
    {
        parent::__construct($resources);
        $this->authorizer = [];
        $this->adapter = [];
        $this->schema = [];
        $this->validator = [];
    }

    /**
     * @param string $resourceType
     * @param string $fqn
     * @return StaticResolver
     */
    public function setAdapter(string $resourceType, string $fqn): StaticResolver
    {
        $this->adapter[$resourceType] = $fqn;

        return $this;
    }

    /**
     * @param string $resourceType
     * @param string $fqn
     * @return $this
     */
    public function setAuthorizer(string $resourceType, string $fqn): StaticResolver
    {
        $this->authorizer[$resourceType] = $fqn;

        return $this;
    }

    /**
     * @param string $resourceType
     * @param string $fqn
     * @return $this
     */
    public function setSchema(string $resourceType, string $fqn): StaticResolver
    {
        $this->schema[$resourceType] = $fqn;

        return $this;
    }

    /**
     * @param string $resourceType
     * @param string $fqn
     * @return $this
     */
    public function setValidators(string $resourceType, string $fqn): StaticResolver
    {
        $this->validator[$resourceType] = $fqn;

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function resolve($unit, $resourceType)
    {
        $key = lcfirst($unit);

        return $this->{$key}[$resourceType] ?? null;
    }

}
