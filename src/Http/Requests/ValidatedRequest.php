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

namespace CloudCreativity\LaravelJsonApi\Http\Requests;

use CloudCreativity\LaravelJsonApi\Contracts\Auth\AuthorizerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersParserInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validation\DocumentValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\DocumentRequiredException;
use CloudCreativity\LaravelJsonApi\Exceptions\ValidationException;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Routing\Route;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * Class ValidatedRequest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class ValidatedRequest implements ValidatesWhenResolved
{

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Route
     */
    protected $route;

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array|null
     */
    private $data;

    /**
     * @var QueryParametersInterface|null
     */
    private $parameters;

    /**
     * Authorize the request.
     *
     * @return void
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    abstract protected function authorize();

    /**
     * Validate the query parameters.
     *
     * @return void
     * @throws JsonApiException
     */
    abstract protected function validateQuery();

    /**
     * ValidatedRequest constructor.
     *
     * @param Request $httpRequest
     * @param ContainerInterface $container
     * @param Factory $factory
     * @param Route $route
     */
    public function __construct(
        Request $httpRequest,
        ContainerInterface $container,
        Factory $factory,
        Route $route
    ) {
        $this->request = $httpRequest;
        $this->factory = $factory;
        $this->container = $container;
        $this->route = $route;
    }

    /**
     * Get an item from the JSON API document using "dot" notation.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return Arr::get($this->all(), $key, $default);
    }

    /**
     * Get the JSON API document as an array.
     *
     * @return array
     */
    public function all()
    {
        if (is_array($this->data)) {
            return $this->data;
        }

        return $this->data = $this->route->getCodec()->all($this->request);
    }

    /**
     * @param $key
     * @return UploadedFile|null
     */
    public function file($key): ?UploadedFile
    {
        $file = $this->get($key);

        return ($file instanceof UploadedFile) ? $file : null;
    }

    /**
     * Get parsed query parameters.
     *
     * @param  string|null  $key
     * @param  string|array|null  $default
     * @return string|array|null
     */
    public function query($key = null, $default = null)
    {
        return $this->request->query($key, $default);
    }

    /**
     * Get the JSON API document as an object.
     *
     * @return object
     */
    public function decode()
    {
        return $this->route
            ->getCodec()
            ->document($this->request);
    }

    /**
     * Get the JSON API document as an object.
     *
     * @return object
     */
    public function decodeOrFail()
    {
        if (!$document = $this->decode()) {
            throw new DocumentRequiredException();
        }

        return $document;
    }

    /**
     * Get the domain record type that is subject of the request.
     *
     * @return string
     */
    public function getType()
    {
        return $this->route->getType();
    }

    /**
     * Get the resource type that the request is for.
     *
     * @return string|null
     */
    public function getResourceType()
    {
        return $this->route->getResourceType();
    }

    /**
     * @return QueryParametersInterface
     */
    public function getEncodingParameters()
    {
        if ($this->parameters) {
            return $this->parameters;
        }

        /** @var QueryParametersParserInterface $parser */
        $parser = app(QueryParametersParserInterface::class);

        return $this->parameters = $parser->parseQueryParameters(
            $this->request->query()
        );
    }

    /**
     * @inheritdoc
     */
    public function validateResolved()
    {
        $this->authorize();
        $this->validateQuery();
        $this->validateDocument();
    }

    /**
     * @return Route
     */
    protected function getRoute(): Route
    {
        return $this->route;
    }

    /**
     * Validate the JSON API document.
     *
     * @return void
     * @throws JsonApiException
     */
    protected function validateDocument()
    {
        // no-op
    }

    /**
     * Run the validation and throw an exception if it fails.
     *
     * @param DocumentValidatorInterface|ValidatorInterface $validator
     * @throws ValidationException
     */
    protected function passes($validator)
    {
        if ($validator->fails()) {
            $this->failedValidation($validator);
        }
    }

    /**
     * @param DocumentValidatorInterface|ValidatorInterface $validator
     * @throws ValidationException
     */
    protected function failedValidation($validator)
    {
        if ($validator instanceof ValidatorInterface) {
            throw ValidationException::create($validator);
        }

        throw new ValidationException($validator->getErrors());
    }

    /**
     * @return AuthorizerInterface|null
     */
    protected function getAuthorizer()
    {
        return $this->container->getAuthorizerByResourceType($this->getResourceType());
    }

    /**
     * Get the resource validators.
     *
     * @return ValidatorFactoryInterface|null
     */
    protected function getValidators()
    {
        return $this->container->getValidatorsByResourceType($this->getResourceType());
    }

    /**
     * Get the inverse resource validators.
     *
     * @return ValidatorFactoryInterface|null
     */
    protected function getInverseValidators()
    {
        return $this->container->getValidatorsByResourceType(
            $this->route->getInverseResourceType()
        );
    }

}
