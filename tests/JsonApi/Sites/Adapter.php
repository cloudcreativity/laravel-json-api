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

}
