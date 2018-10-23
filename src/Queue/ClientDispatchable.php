<?php

namespace CloudCreativity\LaravelJsonApi\Queue;

use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;

trait ClientDispatchable
{

    /**
     * @var ClientJob|null
     */
    public $clientJob;

    /**
     * Start a client dispatch.
     *
     * @param mixed ...$args
     * @return ClientDispatch
     */
    public static function client(...$args): ClientDispatch
    {
        $request = request();

        return (new ClientDispatch(new static(...$args)))->forApi(
            json_api()->getName()
        )->forResource(
            $request->route(ResourceRegistrar::PARAM_RESOURCE_TYPE),
            $request->route(ResourceRegistrar::PARAM_RESOURCE_ID) ?: $request->json('data.id')
        );
    }

    /**
     * Was the job dispatched by a client?
     *
     * @return bool
     */
    public function wasClientDispatched(): bool
    {
        return !is_null($this->clientJob);
    }

    /**
     * Get the JSON API that the job belongs to.
     *
     * @return string|null
     */
    public function api(): ?string
    {
        return optional($this->clientJob)->api;
    }

    /**
     * Get the JSON API resource type that the job relates to.
     *
     * @return string|null
     */
    public function resourceType(): ?string
    {
        return optional($this->clientJob)->resource_type;
    }

    /**
     * Get the JSON API resource id that the job relates to.
     *
     * @return string|null
     */
    public function resourceId(): ?string
    {
        return optional($this->clientJob)->resource_id;
    }
}
