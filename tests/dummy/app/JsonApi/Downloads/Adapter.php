<?php

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
