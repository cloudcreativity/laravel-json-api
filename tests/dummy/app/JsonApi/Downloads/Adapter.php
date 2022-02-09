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

namespace DummyApp\JsonApi\Downloads;

use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use DummyApp\Download;
use DummyApp\Jobs\CreateDownload;
use DummyApp\Jobs\DeleteDownload;
use DummyApp\Jobs\ReplaceDownload;
use Illuminate\Support\Collection;

class Adapter extends AbstractAdapter
{

    /**
     * Adapter constructor.
     *
     * @param StandardStrategy $paging
     */
    public function __construct(StandardStrategy $paging)
    {
        parent::__construct(new Download(), $paging);
    }

    /**
     * @param Download $download
     * @return AsynchronousProcess
     */
    protected function creating(Download $download)
    {
        return CreateDownload::client($download->category)->dispatch();
    }

    /**
     * @param Download $download
     * @return AsynchronousProcess
     */
    protected function updating(Download $download)
    {
        return ReplaceDownload::client($download)->dispatch();
    }

    /**
     * @param Download $download
     * @return AsynchronousProcess
     */
    protected function deleting(Download $download)
    {
        return DeleteDownload::client($download)->dispatch();
    }

    /**
     * @inheritDoc
     */
    protected function filter($query, Collection $filters)
    {
        // noop
    }

}
