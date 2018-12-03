<?php

namespace CloudCreativity\LaravelJsonApi\Queue;

use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;

class UpdateClientProcess
{

    /**
     * Handle the event.
     *
     * @param JobProcessed|JobFailed $event
     * @return void
     */
    public function handle($event): void
    {
        if (!$job = $this->deserialize($event->job)) {
            return;
        }

        $clientJob = $job->clientJob ?? null;

        if (!$clientJob instanceof AsynchronousProcess) {
            return;
        }

        $clientJob->processed($event->job);
    }

    /**
     * @param Job $job
     * @return mixed|null
     */
    private function deserialize(Job $job)
    {
        $data = $this->payload($job)['data'] ?? [];
        $command = $data['command'] ?? null;

        return is_string($command) ? unserialize($command) : null;
    }

    /**
     * @param Job $job
     * @return array
     */
    private function payload(Job $job): array
    {
        return json_decode($job->getRawBody(), true) ?: [];
    }
}
