<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Http\Controllers;

use CloudCreativity\JsonApi\Contracts\Http\ApiInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface as JsonApiRequest;
use CloudCreativity\LaravelJsonApi\Http\Responses\ReplyTrait;
use CloudCreativity\LaravelJsonApi\Tests\Entities\Site;
use CloudCreativity\LaravelJsonApi\Tests\JsonApi\Sites;
use Illuminate\Routing\Controller;

class SitesController extends Controller
{

    use ReplyTrait;

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
     * @param ApiInterface $api
     * @param JsonApiRequest $request
     * @return mixed
     */
    public function index(ApiInterface $api, JsonApiRequest $request)
    {
        $store = $api->getStore();

        return $this->reply()->content($store->query(
            $request->getResourceType(),
            $request->getParameters()
        ));
    }

    /**
     * @param JsonApiRequest $request
     * @return mixed
     */
    public function create(JsonApiRequest $request)
    {
        $resource = $request->getDocument()->getResource();
        $record = new Site($resource->getId()); // client generated id.
        $this->hydrator->hydrate($request->getDocument()->getResource(), $record);
        $record->save();

        return $this->reply()->created($record);
    }

    /**
     * @param JsonApiRequest $request
     * @return mixed
     */
    public function read(JsonApiRequest $request)
    {
        return $this->reply()->content($request->getRecord());
    }

    /**
     * @param JsonApiRequest $request
     * @return mixed
     */
    public function update(JsonApiRequest $request)
    {
        /** @var Site $record */
        $record = $request->getRecord();
        $resource = $request->getDocument()->getResource();
        $this->hydrator->hydrate($resource, $record);
        $record->save();

        return $this->reply()->content($record);
    }

    /**
     * @param JsonApiRequest $request
     * @return mixed
     */
    public function delete(JsonApiRequest $request)
    {
        /** @var Site $record */
        $record = $request->getRecord();
        $record->delete();

        return $this->reply()->noContent();
    }
}
