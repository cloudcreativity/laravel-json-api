<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Sites;

use CloudCreativity\JsonApi\Contracts\Store\AdapterInterface;
use CloudCreativity\LaravelJsonApi\Tests\Entities\SiteRepository;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class Adapter implements AdapterInterface
{

    /**
     * @var SiteRepository
     */
    private $repository;

    /**
     * Adapter constructor.
     *
     * @param SiteRepository $repository
     */
    public function __construct(SiteRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @inheritDoc
     */
    public function query(EncodingParametersInterface $parameters)
    {
        return $this->repository->all();
    }

    /**
     * @inheritDoc
     */
    public function queryRecord($resourceId, EncodingParametersInterface $parameters)
    {
        return $this->find($resourceId);
    }

    /**
     * @inheritDoc
     */
    public function queryRelated($record, $relationshipName, EncodingParametersInterface $parameters)
    {
        // TODO: Implement queryRelated() method.
    }

    /**
     * @inheritDoc
     */
    public function queryRelationship($record, $relationshipName, EncodingParametersInterface $parameters)
    {
        // TODO: Implement queryRelationship() method.
    }

    /**
     * @inheritdoc
     */
    public function exists($resourceId)
    {
        return !is_null($this->find($resourceId));
    }

    /**
     * @inheritdoc
     */
    public function find($resourceId)
    {
        return $this->repository->find($resourceId);
    }

    /**
     * @inheritDoc
     */
    public function findMany(array $resourceIds)
    {
        return collect($resourceIds)->map(function ($resourceId) {
            return $this->find($resourceId);
        })->filter()->all();
    }

    /**
     * @inheritDoc
     */
    public function inverse($relationshipName)
    {
        // TODO: Implement inverse() method.
    }

}
