<?php

namespace CloudCreativity\LaravelJsonApi\Queue;

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
        return new ClientDispatch(
            new ClientJob(),
            new static(...$args)
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

    /**
     * Set the resource that was created by the job.
     *
     * If a job is creating a new resource, this method can be used to update
     * the client job with the created resource. This method does nothing if the
     * job was not dispatched by a client.
     *
     * @param $resource
     * @return void
     */
    public function didCreate($resource): void
    {
        if ($this->wasClientDispatched()) {
            $this->clientJob->setResource($resource)->save();
        }
    }
}
