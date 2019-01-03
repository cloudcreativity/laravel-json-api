<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Queue;

use CloudCreativity\LaravelJsonApi\Queue\ClientDispatchable;
use DummyApp\Download;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestJob implements ShouldQueue
{

    use Dispatchable,
        ClientDispatchable,
        SerializesModels,
        InteractsWithQueue,
        Queueable;

    /**
     * @var bool
     */
    public $ex = false;

    /**
     * @var int
     */
    public $tries = 2;

    /**
     * Execute the job.
     *
     * @return Download
     * @throws \Exception
     */
    public function handle(): Download
    {
        if ($this->ex) {
            throw new \LogicException('Boom.');
        }

        $download = factory(Download::class)->create();
        $this->didCreate($download);

        return $download;
    }
}
