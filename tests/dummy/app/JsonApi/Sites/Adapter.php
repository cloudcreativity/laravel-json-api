<?php

namespace DummyApp\JsonApi\Sites;

use CloudCreativity\JsonApi\Adapter\AbstractResourceAdaptor;
use CloudCreativity\JsonApi\Adapter\HydratesAttributesTrait;
use CloudCreativity\JsonApi\Contracts\Object\RelationshipsInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\JsonApi\Utils\Str;
use DummyApp\Entities\Site;
use DummyApp\Entities\SiteRepository;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class Adapter extends AbstractResourceAdaptor
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
     * @param Site $record
     * @param EncodingParametersInterface $params
     * @return bool
     */
    public function delete($record, EncodingParametersInterface $params)
    {
        $this->repository->remove($record);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function related($relationshipName)
    {
        throw new RuntimeException('Not supported');
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
    protected function createRecord(ResourceObjectInterface $resource)
    {
        return new Site($resource->getId());
    }

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


    /**
     * @inheritDoc
     */
    protected function hydrateRelationships(
        $record,
        RelationshipsInterface $relationships,
        EncodingParametersInterface $parameters
    ) {
        // no-op
    }

    /**
     * @param Site $record
     */
    protected function persist($record)
    {
        $this->repository->store($record);
    }

}
