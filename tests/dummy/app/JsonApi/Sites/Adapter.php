<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

namespace DummyApp\JsonApi\Sites;

use CloudCreativity\LaravelJsonApi\Adapter\AbstractResourceAdapter;
use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use DummyApp\Entities\Site;
use DummyApp\Entities\SiteRepository;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class Adapter extends AbstractResourceAdapter
{

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
    protected function createRecord(ResourceObject $resource)
    {
        return new Site($resource->getId());
    }

    /**
     * @inheritDoc
     */
    protected function destroy($record)
    {
        $this->repository->remove($record);

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function fillAttributes($record, Collection $attributes)
    {
        foreach ($attributes as $field => $value) {
            $this->fillAttribute($record, $field, $value);
        }
    }

    /**
     * @param object $record
     * @param string $field
     * @param mixed $value
     * @return void
     */
    protected function fillAttribute($record, $field, $value)
    {
        $method = 'set' . Str::classify($field);

        call_user_func([$record, $method], $value);
    }

    /**
     * @inheritdoc
     */
    protected function persist($record)
    {
        $this->repository->store($record);
    }

}
