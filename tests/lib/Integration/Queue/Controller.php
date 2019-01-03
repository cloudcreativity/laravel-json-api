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

use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use DummyApp\Download;
use DummyApp\Jobs\CreateDownload;
use DummyApp\Jobs\DeleteDownload;
use DummyApp\Jobs\ReplaceDownload;

class Controller extends JsonApiController
{

    /**
     * @return AsynchronousProcess
     */
    protected function creating(): AsynchronousProcess
    {
        return CreateDownload::client('create')->dispatch();
    }

    /**
     * @param Download $download
     * @return AsynchronousProcess
     */
    protected function updating(Download $download): AsynchronousProcess
    {
        return ReplaceDownload::client($download)->dispatch();
    }

    /**
     * @param Download $download
     * @return AsynchronousProcess
     */
    protected function deleting(Download $download): AsynchronousProcess
    {
        return DeleteDownload::client($download)->dispatch();
    }
}
