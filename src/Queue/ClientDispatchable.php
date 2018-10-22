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
     * @param string $resourceType
     * @param mixed ...$args
     * @return ClientDispatch
     */
    public static function client(string $resourceType, ...$args): ClientDispatch
    {
        return new ClientDispatch($resourceType, new static(...$args));
    }
}
