<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Http\Controllers;

use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Http\Controllers\CreatesResponses;
use CloudCreativity\LaravelJsonApi\Tests\Entities\Site;
use CloudCreativity\LaravelJsonApi\Tests\JsonApi\Sites;
use Illuminate\Routing\Controller;

class SitesController extends Controller
{

    use CreatesResponses;

    /**
     * @param StoreInterface $store
     * @param RequestInterface $request
     * @return mixed
     */
    public function index(StoreInterface $store, RequestInterface $request)
    {
        $results = $store->query(
            $request->getResourceType(),
            $request->getParameters()
        );

        return $this->reply()->content($results);
    }

    /**
     * @param Sites\Hydrator $hydrator
     * @param ResourceObjectInterface $resource
     * @return mixed
     */
    public function create(Sites\Hydrator $hydrator, ResourceObjectInterface $resource)
    {
        $record = new Site($resource->getId()); // client generated id.
        $hydrator->hydrate($resource, $record);
        $record->save();

        return $this->reply()->created($record);
    }

    /**
     * @param Site $record
     * @return mixed
     */
    public function read(Site $record)
    {
        return $this->reply()->content($record);
    }

    /**
     * @param Sites\Hydrator $hydrator
     * @param ResourceObjectInterface $resource
     * @param Site $record
     * @return mixed
     */
    public function update(Sites\Hydrator $hydrator, ResourceObjectInterface $resource, Site $record)
    {
        $hydrator->hydrate($resource, $record);
        $record->save();

        return $this->reply()->content($record);
    }

    /**
     * @param Site $site
     * @return mixed
     */
    public function delete(Site $site)
    {
        $site->delete();

        return $this->reply()->noContent();
    }
}
