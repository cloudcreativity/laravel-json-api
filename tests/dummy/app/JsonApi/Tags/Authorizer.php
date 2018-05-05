<?php

namespace DummyApp\JsonApi\Tags;

use CloudCreativity\LaravelJsonApi\Auth\AbstractResourceAuthorizer;
use DummyApp\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class Authorizer extends AbstractResourceAuthorizer
{

    /**
     * @inheritdoc
     */
    public function index($request)
    {
        $this->authenticate();
    }

    /**
     * @inheritdoc
     */
    public function create($request)
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
