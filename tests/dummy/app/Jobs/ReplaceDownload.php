<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

namespace DummyApp\Jobs;

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Queue\ClientDispatchable;
use DummyApp\Download;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReplaceDownload implements ShouldQueue
{

    use Dispatchable,
        ClientDispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var Download
     */
    public $download;

    /**
     * ReplaceDownload constructor.
     *
     * @param Download $download
     */
    public function __construct(Download $download)
    {
        $this->download = $download;
    }

    /**
     * @return Carbon
     */
    public function retryUntil(): Carbon
    {
        return now()->addSeconds(25);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // no-op
    }
}
