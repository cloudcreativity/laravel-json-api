<?php

namespace CloudCreativity\LaravelJsonApi\Validation\Document;

use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Document\Error;

class ErrorFactory
{

    /**
     * Create an error for a member that is required.
     *
     * @param string $path
     * @param string $member
     * @return ErrorInterface
     */
    public function memberRequired($path, $member)
    {
        return new Error(
            null,
            null,
            Response::HTTP_BAD_REQUEST,
            null,
            'Required Member',
            "The member '{$member}' is required.",
            $this->pointer($path)
        );
    }

    /**
     * Create an error for a member that must be an object.
     *
     * @param string $path
     * @param string $member
     * @return ErrorInterface
     */
    public function memberNotObject($path, $member)
    {
        return new Error(
            null,
            null,
            Response::HTTP_BAD_REQUEST,
            null,
            'Object Expected',
            "The member '{$member}' must be an object.",
            $this->pointer($path, $member)
        );
    }

    /**
     * Create an error for a member that must be a string.
     *
     * @param string $path
     * @param string $member
     * @return ErrorInterface
     */
    public function memberNotString($path, $member)
    {
        return new Error(
            null,
            null,
            Response::HTTP_BAD_REQUEST,
            null,
            'String Expected',
            "The member '{$member}' must be a string.",
            $this->pointer($path, $member)
        );
    }

    /**
     * Create an error for a member that cannot be an empty value.
     *
     * @param $path
     * @param $member
     * @return ErrorInterface
     */
    public function memberEmpty($path, $member)
    {
        return new Error(
            null,
            null,
            Response::HTTP_BAD_REQUEST,
            null,
            'Value Expected',
            "The member '{$member}' cannot be empty.",
            $this->pointer($path, $member)
        );
    }

    /**
     * Create an error for when the resource type is not supported by the endpoint.
     *
     * @param $actual
     * @param string $path
     * @return ErrorInterface
     */
    public function resourceTypeNotSupported($actual, $path = '/data/type')
    {
        return new Error(
            null,
            null,
            Response::HTTP_CONFLICT,
            null,
            'Not Supported',
            "Resource type '{$actual}' is not supported by this endpoint.",
            $this->pointer($path)
        );
    }

    /**
     * Create an error for when the resource id is not supported by the endpoint.
     *
     * @param $actual
     * @param string $path
     * @return ErrorInterface
     */
    public function resourceIdNotSupported($actual, $path = '/data/id')
    {
        return new Error(
            null,
            null,
            Response::HTTP_CONFLICT,
            null,
            'Not Supported',
            "Resource id '{$actual}' is not supported by this endpoint.",
            $this->pointer($path)
        );
    }

    /**
     * Create an error for a resource identifier that does not exist.
     *
     * @param string $path
     * @return Error
     */
    public function resourceDoesNotExist($path)
    {
        return new Error(
            null,
            null,
            Response::HTTP_NOT_FOUND,
            null,
            'Invalid Relationship',
            'The related resource does not exist.',
            $this->pointer($path)
        );
    }

    /**
     * Create a source pointer for the specified path and optional member at that path.
     *
     * @param string $path
     * @param string|null $member
     * @return array
     */
    protected function pointer($path, $member = null)
    {
        if (!$member) {
            $pointer = $path;
        } else {
            $path = rtrim($path, '/');
            $pointer = $member ? sprintf('%s/%s', $path, $member) : $path;
        }

        return [Error::SOURCE_POINTER => $pointer];
    }
}
