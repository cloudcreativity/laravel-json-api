<?php

namespace DummyApp\JsonApi\Downloads;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use DummyApp\Download;
use DummyApp\Jobs\CreateDownload;
use DummyApp\Jobs\DeleteDownload;
use DummyApp\Jobs\ReplaceDownload;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

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
     * @inheritdoc
     */
    public function create(array $document, EncodingParametersInterface $parameters)
    {
        return CreateDownload::client(
            array_get($document, 'data.attributes.category')
        )->dispatch();
    }

    /**
     * @inheritdoc
     */
    public function update($record, array $document, EncodingParametersInterface $parameters)
    {
        return ReplaceDownload::client($record)->dispatch();
    }

    /**
     * @inheritdoc
     */
    public function delete($record, EncodingParametersInterface $params)
    {
        return DeleteDownload::client($record)->dispatch();
    }

    /**
     * @inheritDoc
     */
    protected function filter($query, Collection $filters)
    {
        // noop
    }

}
