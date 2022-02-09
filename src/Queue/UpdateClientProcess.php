<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Queue;

use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

        if (!is_string($command)) {
            return null;
        }

        try {
            return unserialize($command) ?: null;
        } catch (ModelNotFoundException $ex) {
            return null;
        }
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
