<?php

namespace DummyApp\JsonApi\Tags;

use CloudCreativity\LaravelJsonApi\Auth\AbstractAuthorizer;
use DummyApp\User;

class Authorizer extends AbstractAuthorizer
{

    /**
     * @inheritdoc
     */
    public function index($type, $request)
    {
        $this->authenticate();
    }

    /**
     * @inheritdoc
     */
    public function create($type, $request)
    {
        $this->can('author', User::class);
    }

    /**
     * @inheritdoc
     */
    public function read($record, $request)
    {
        $this->authenticate();
    }

    /**
     * @inheritdoc
     */
    public function update($record, $request)
    {
        $this->can('admin', User::class);
    }

    /**
     * @inheritdoc
     */
    public function delete($record, $request)
    {
        $this->update($record, $request);
    }

}
