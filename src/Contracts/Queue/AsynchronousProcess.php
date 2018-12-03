<?php

namespace CloudCreativity\LaravelJsonApi\Contracts\Queue;

use CloudCreativity\LaravelJsonApi\Queue\ClientDispatch;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;

/**
 * Interface AsynchronousProcess
 *
 * @package CloudCreativity\LaravelJsonApi
 */
interface AsynchronousProcess
{

    /**
     * Get the resource type that the process relates to.
     *
     * @return string
     */
    public function getResourceType(): string;

    /**
     * Get the location of the resource that the process relates to, if known.
     *
     * @return string|null
     */
    public function getLocation(): ?string;

    /**
     * Is the process still pending?
     *
     * @return bool
     */
    public function isPending(): bool;

    /**
     * Mark the process as being dispatched.
     *
     * @param ClientDispatch $dispatch
     * @return void
     */
    public function dispatching(ClientDispatch $dispatch): void;

    /**
     * Mark the process as processed.
     *
     * @param JobContract|Job $job
     * @return void
     */
    public function processed($job): void;

}
