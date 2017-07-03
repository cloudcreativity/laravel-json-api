<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Http\Controllers;

use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface;
use CloudCreativity\LaravelJsonApi\Http\Controllers\CreatesResponses;
use CloudCreativity\LaravelJsonApi\Tests\Entities\Site;
use CloudCreativity\LaravelJsonApi\Tests\JsonApi\Sites;
use Illuminate\Routing\Controller;

class SitesController extends Controller
{

    use CreatesResponses;

    /**
     * @var Sites\Hydrator
     */
    private $hydrator;

    /**
     * SitesController constructor.
     *
     * @param Sites\Hydrator $hydrator
     */
    public function __construct(Sites\Hydrator $hydrator)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * @param RequestInterface $request
     * @return mixed
     */
    public function index(RequestInterface $request)
    {
        $results = $this->api()->getStore()->query(
            $request->getResourceType(),
            $request->getParameters()
        );

        return $this->reply()->content($results);
    }

    /**
     * @param RequestInterface $request
     * @return mixed
     */
    public function create(RequestInterface $request)
    {
        $resource = $request->getDocument()->getResource();
        $record = new Site($resource->getId()); // client generated id.
        $this->hydrator->hydrate($request->getDocument()->getResource(), $record);
        $record->save();

        return $this->reply()->created($record);
    }

    /**
     * @param RequestInterface $request
     * @return mixed
     */
    public function read(RequestInterface $request)
    {
        return $this->reply()->content($request->getRecord());
    }

    /**
     * @param RequestInterface $request
     * @return mixed
     */
    public function update(RequestInterface $request)
    {
        /** @var Site $record */
        $record = $request->getRecord();
        $resource = $request->getDocument()->getResource();
        $this->hydrator->hydrate($resource, $record);
        $record->save();

        return $this->reply()->content($record);
    }

    /**
     * @param RequestInterface $request
     * @return mixed
     */
    public function delete(RequestInterface $request)
    {
        /** @var Site $record */
        $record = $request->getRecord();
        $record->delete();

        return $this->reply()->noContent();
    }
}
