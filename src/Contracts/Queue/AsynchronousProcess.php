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
