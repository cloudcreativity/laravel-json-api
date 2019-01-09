<?php

namespace CloudCreativity\LaravelJsonApi\Http\Requests\Concerns;

use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Routing\Route;

trait ResourceRequest
{

    /**
     * @return Route
     */
    abstract protected function getRoute(): Route;

    /**
     * Get the resource id that the request is for.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return $this->getRoute()->getResourceId();
    }

    /**
     * Get the domain record that the request relates to.
     *
     * @return mixed
     */
    public function getRecord()
    {
        if (!$record = $this->getRoute()->getResource()) {
            throw new RuntimeException('Expecting resource binding to be substituted.');
        }

        return $record;
    }
}
