<?php

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
