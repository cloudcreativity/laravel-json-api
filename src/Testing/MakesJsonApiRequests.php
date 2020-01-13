<?php

/**
 * Copyright 2020 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Testing;

use CloudCreativity\LaravelJsonApi\Api\Api;
use Illuminate\Contracts\Routing\UrlRoutable;
use InvalidArgumentException;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use PHPUnit\Framework\Assert;

/**
 * Class MakesJsonApiRequests
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait MakesJsonApiRequests
{

    /**
     * The API to test, if empty uses the default API.
     *
     * @var string
     * @deprecated 2.0.0 use `$baseApiUrl` instead.
     */
    protected $api = '';

    /**
     * The base URL of the API to test.
     *
     * @var string
     */
    protected $baseApiUrl = '';

    /**
     * The resource type to test.
     *
     * @var string
     */
    protected $resourceType = '';

    /**
     * The resource type expected in the response.
     *
     * @var string
     */
    protected $expectedResourceType = '';

    /**
     * The test request Accept header media type.
     *
     * @var string
     */
    protected $acceptMediaType = MediaTypeInterface::JSON_API_MEDIA_TYPE;

    /**
     * The test request content type.
     *
     * @var string
     */
    protected $contentMediaType = MediaTypeInterface::JSON_API_MEDIA_TYPE;

    /**
     * Visit the given URI with a JSON API request.
     *
     * @param string $method
     * @param string $uri
     * @param iterable $queryParams
     * @param iterable $data
     * @param iterable $headers
     * @return TestResponse
     */
    protected function jsonApi(
        string $method,
        string $uri,
        iterable $queryParams = [],
        iterable $data = [],
        iterable $headers = []
    ): TestResponse
    {
        $data = collect($data)->jsonSerialize();
        $queryParams = collect($queryParams);

        if ($queryParams->isNotEmpty()) {
            $uri .= '?' . http_build_query($queryParams->toArray());
        }

        return $this->json($method, $uri, $data, $this->normalizeHeaders($headers));
    }

    /**
     * @param string $uri
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     */
    protected function getJsonApi(
        string $uri,
        iterable $queryParams = [],
        iterable $headers = []
    ): TestResponse
    {
        return $this->jsonApi('GET', $uri, $queryParams, [], $headers);
    }

    /**
     * @param string $uri
     * @param iterable $queryParams
     * @param iterable $data
     * @param iterable $headers
     * @return TestResponse
     */
    protected function postJsonApi(
        string $uri,
        iterable $queryParams = [],
        iterable $data = [],
        iterable $headers = []
    ): TestResponse
    {
        return $this->jsonApi('POST', $uri, $queryParams, $data, $headers);
    }

    /**
     * @param string $uri
     * @param iterable $queryParams
     * @param iterable $data
     * @param iterable $headers
     * @return TestResponse
     */
    protected function patchJsonApi(
        string $uri,
        iterable $queryParams = [],
        iterable $data = [],
        iterable $headers = []
    ): TestResponse
    {
        return $this->jsonApi('PATCH', $uri, $queryParams, $data, $headers);
    }

    /**
     * @param string $uri
     * @param iterable $queryParams
     * @param iterable $data
     * @param iterable $headers
     * @return TestResponse
     */
    protected function deleteJsonApi(
        string $uri,
        iterable $queryParams = [],
        iterable $data = [],
        iterable $headers = []
    ): TestResponse
    {
        return $this->jsonApi('DELETE', $uri, $queryParams, $data, $headers);
    }

    /**
     * @param $response
     * @return TestResponse
     */
    protected function createTestResponse($response)
    {
        return new TestResponse($response, $this->expectedResourceType());
    }

    /**
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     */
    protected function doSearch(iterable $queryParams = [], iterable $headers = []): TestResponse
    {
        $uri = $this->resourceUrl();

        return $this->getJsonApi($uri, $queryParams, $headers);
    }

    /**
     * @param mixed $ids
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     */
    protected function doSearchById($ids, iterable $queryParams = [], iterable $headers = []): TestResponse
    {
        $queryParams['filter'] = $queryParams['filter'] ?? [];
        $queryParams['filter']['id'] = $this->normalizeIds($ids);

        return $this->doSearch($queryParams, $headers);
    }

    /**
     * Assert that the resource's search (index) route has not been registered.
     *
     * @return void
     * @deprecated 2.0.0 use `doSearch` and check for 404/405 status.
     */
    protected function assertCannotSearch()
    {
        $searchable = true;

        try {
            $this->api()->url()->index($this->resourceType(), $this->addDefaultRouteParams([]));
        } catch (InvalidArgumentException $ex) {
            $searchable = false;
        }

        $this->assertFalse($searchable, 'Resource search route exists.');
    }

    /**
     * @param mixed $data
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     */
    protected function doCreate($data, iterable $queryParams = [], iterable $headers = []): TestResponse
    {
        $data = collect($data)->jsonSerialize();
        $uri = $this->resourceUrl();

        return $this->postJsonApi($uri, $queryParams, compact('data'), $headers);
    }

    /**
     * Assert that the resource's create route has not been registered.
     *
     * @return void
     * @deprecated 2.0.0 use `doCreate` and check for 404/405 status.
     */
    protected function assertCannotCreate()
    {
        $creatable = true;

        try {
            $this->api()->url()->create($this->resourceType(), $this->addDefaultRouteParams([]));
        } catch (InvalidArgumentException $ex) {
            $creatable = false;
        }

        $this->assertFalse($creatable, 'Resource create route exists.');
    }

    /**
     * @param mixed $resourceId
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     */
    protected function doRead($resourceId, iterable $queryParams = [], iterable $headers = []): TestResponse
    {
        $uri = $this->resourceUrl($resourceId);

        return $this->getJsonApi($uri, $queryParams, $headers);
    }

    /**
     * Assert that the resource's read route has not been registered.
     *
     * @return void
     * @deprecated 2.0.0 use `doRead` and check for 404/405 status.
     */
    protected function assertCannotRead()
    {
        $readable = true;

        try {
            $this->api()->url()->read($this->resourceType(), '1', $this->addDefaultRouteParams([]));
        } catch (InvalidArgumentException $ex) {
            $readable = false;
        }

        $this->assertFalse($readable, 'Resource read route exists.');
    }

    /**
     * @param mixed $data
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     */
    protected function doUpdate($data, iterable $queryParams = [], iterable $headers = []): TestResponse
    {
        $data = collect($data)->jsonSerialize();

        if (!$id = $data['id'] ?? null) {
            Assert::fail('Expecting data for test request to contain a resource id.');
        }

        $uri = $this->resourceUrl($id);

        return $this->patchJsonApi($uri, $queryParams, compact('data'), $headers);
    }

    /**
     * Assert that the resource's update route has not been registered.
     *
     * @return void
     * @deprecated 2.0.0 use `doUpdate` and check for 404/405 status.
     */
    protected function assertCannotUpdate()
    {
        $exists = true;

        try {
            $this->api()->url()->update($this->resourceType(), '1', $this->addDefaultRouteParams([]));
        } catch (InvalidArgumentException $ex) {
            $exists = false;
        }

        $this->assertFalse($exists, 'Resource update route exists.');
    }

    /**
     * @param mixed $resourceId
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     */
    protected function doDelete($resourceId, iterable $queryParams = [], iterable $headers = []): TestResponse
    {
        $uri = $this->resourceUrl($resourceId);

        return $this->deleteJsonApi($uri, $queryParams, [], $headers);
    }

    /**
     * Assert that the resource's delete route has not been registered.
     *
     * @return void
     * @deprecated 2.0.0 use `doDelete` and check for 404/405 status.
     */
    protected function assertCannotDelete()
    {
        $deletable = true;

        try {
            $this->api()->url()->delete($this->resourceType(), '1', $this->addDefaultRouteParams([]));
        } catch (InvalidArgumentException $ex) {
            $deletable = false;
        }

        $this->assertFalse($deletable, 'Resource delete route exists.');
    }

    /**
     * @param mixed $resourceId
     * @param string $field
     *      the relationship field name.
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     */
    protected function doReadRelated(
        $resourceId,
        string $field,
        iterable $queryParams = [],
        iterable $headers = []
    ): TestResponse
    {
        $uri = $this->resourceUrl($resourceId, $field);

        return $this->getJsonApi($uri, $queryParams, $headers);
    }

    /**
     * Assert that the related resource route has not been registered for the supplied relationship name.
     *
     * @param $field
     *      the relationship field name.
     * @return void
     * @deprecated 2.0.0 use `doReadRelated` and check for 404/405 status.
     */
    protected function assertCannotReadRelated($field)
    {
        $readable = true;

        try {
            $this->api()->url()->relatedResource(
                $this->resourceType(),
                '1',
                $field,
                $this->addDefaultRouteParams([])
            );
        } catch (InvalidArgumentException $ex) {
            $readable = false;
        }

        $this->assertFalse($readable, "Related resource $field route exists.");
    }

    /**
     * @param mixed $resourceId
     * @param string $field
     *      the relationship field name.
     * @param array $queryParams
     * @param array $headers
     * @return TestResponse
     */
    protected function doReadRelationship(
        $resourceId,
        string $field,
        iterable $queryParams = [],
        iterable $headers = []
    ): TestResponse
    {
        $uri = $this->resourceUrl($resourceId, 'relationships', $field);

        return $this->getJsonApi($uri, $queryParams, $headers);
    }

    /**
     * Assert that the read relationship route has not been registered for the supplied relationship name.
     *
     * @param $field
     *      the relationship field name.
     * @return void
     * @deprecated 2.0.0 use `doReadRelationship` and check for 404/405 status.
     */
    protected function assertCannotReadRelationship($field)
    {
        $readable = true;

        try {
            $this->api()->url()->readRelationship(
                $this->resourceType(),
                '1',
                $field,
                $this->addDefaultRouteParams([])
            );
        } catch (InvalidArgumentException $ex) {
            $readable = false;
        }

        $this->assertFalse($readable, "Read relationship $field route exists.");
    }

    /**
     * @param mixed $resourceId
     * @param string $field
     *      the relationship field name.
     * @param mixed $data
     * @param array $queryParams
     * @param array $headers
     * @return TestResponse
     */
    protected function doReplaceRelationship(
        $resourceId,
        string $field,
        $data,
        iterable $queryParams = [],
        iterable $headers = []
    ): TestResponse
    {
        if (!is_null($data)) {
            $data = collect($data)->jsonSerialize();
        }

        $uri = $this->resourceUrl($resourceId, 'relationships', $field);

        return $this->patchJsonApi($uri, $queryParams, compact('data'), $headers);
    }

    /**
     * Assert that the replace relationship route has not been registered for the supplied relationship name.
     *
     * @param $field
     *      the relationship field name.
     * @return void
     * @deprecated 2.0.0 use `doReplaceRelationship` and check for 404/405 status.
     */
    protected function assertCannotReplaceRelationship($field)
    {
        $replaceable = true;

        try {
            $this->api()->url()->replaceRelationship(
                $this->resourceType(),
                '1',
                $field,
                $this->addDefaultRouteParams([])
            );
        } catch (InvalidArgumentException $ex) {
            $replaceable = false;
        }

        $this->assertFalse($replaceable, "Replace relationship $field route exists.");
    }

    /**
     * @param mixed $resourceId
     * @param string $field
     *      the relationship field name.
     * @param mixed $data
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     */
    protected function doAddToRelationship(
        $resourceId,
        string $field,
        $data,
        iterable $queryParams = [],
        iterable $headers = []
    ): TestResponse
    {
        $data = collect($data)->jsonSerialize();
        $uri = $this->resourceUrl($resourceId, 'relationships', $field);

        return $this->postJsonApi($uri, $queryParams, compact('data'), $headers);
    }

    /**
     * Assert that the add-to relationship route has not been registered for the supplied relationship name.
     *
     * @param $field
     *      the relationship field name.
     * @return void
     * @deprecated 2.0.0 use `doAddToRelationship` and check for 404/405 status.
     */
    protected function assertCannotAddToRelationship($field)
    {
        $replaceable = true;

        try {
            $this->api()->url()->addRelationship(
                $this->resourceType(),
                '1',
                $field,
                $this->addDefaultRouteParams([])
            );
        } catch (InvalidArgumentException $ex) {
            $replaceable = false;
        }

        $this->assertFalse($replaceable, "Add to relationship $field route exists.");
    }

    /**
     * @param mixed $resourceId
     * @param string $field
     *      the relationship field name.
     * @param mixed $data
     * @param array $queryParams
     * @param array $headers
     * @return TestResponse
     */
    protected function doRemoveFromRelationship(
        $resourceId,
        string $field,
        $data,
        iterable $queryParams = [],
        iterable $headers = []
    ): TestResponse
    {
        $data = collect($data)->jsonSerialize();
        $uri = $this->resourceUrl($resourceId, 'relationships', $field);

        return $this->deleteJsonApi($uri, $queryParams, ['data' => $data], $headers);
    }

    /**
     * Assert that the remove-from relationship route has not been registered for the supplied relationship name.
     *
     * @param $field
     *      the relationship field name.
     * @return void
     * @deprecated 2.0.0 use `doRemoveFromRelationship` and check for 404/405 status.
     */
    protected function assertCannotRemoveFromRelationship($field)
    {
        $replaceable = true;

        try {
            $this->api()->url()->removeRelationship(
                $this->resourceType(),
                '1',
                $field,
                $this->addDefaultRouteParams([])
            );
        } catch (InvalidArgumentException $ex) {
            $replaceable = false;
        }

        $this->assertFalse($replaceable, "Remove from relationship $field route exists.");
    }

    /**
     * Assert that the resource's create, update and delete routes do not exist.
     *
     * @return void
     * @deprecated 2.0.0
     */
    protected function assertReadOnly()
    {
        $this->assertCannotCreate();
        $this->assertCannotUpdate();
        $this->assertCannotDelete();
    }

    /**
     * Assert that the resource relationship's replace, add-to and remove-from routes do not exist.
     *
     * @param $field
     *      the relationship field name.
     * @return void
     * @deprecated 2.0.0
     */
    protected function assertRelationshipIsReadOnly($field)
    {
        $this->assertCannotReplaceRelationship($field);
        $this->assertCannotAddToRelationship($field);
        $this->assertCannotRemoveFromRelationship($field);
    }

    /**
     * Set the API to test.
     *
     * @param string|null $api
     *      the API, or null to test the default API.
     * @return $this
     * @deprecated 2.0.0 use `withBaseApiUrl()`.
     */
    protected function withApi(?string $api): self
    {
        $this->api = $api;

        return $this;
    }

    /**
     * Set the resource type to test.
     *
     * @param string $resourceType
     * @return $this
     */
    protected function withResourceType(string $resourceType): self
    {
        $this->resourceType = $resourceType;

        return $this;
    }

    /**
     * Get the resource type that is being tested.
     *
     * @return string
     */
    protected function resourceType(): string
    {
        if (empty($this->resourceType)) {
            Assert::fail('You must set a resource type property on your test case.');
        }

        return $this->resourceType;
    }

    /**
     * Set the Accept header media type for test requests.
     *
     * @param string $mediaType
     * @return $this
     */
    protected function withAcceptMediaType(string $mediaType): self
    {
        $this->acceptMediaType = $mediaType;

        return $this;
    }

    /**
     * Get the media type to use for the Accept header.
     *
     * @return string
     */
    protected function acceptMediaType(): string
    {
        return $this->acceptMediaType;
    }

    /**
     * Set the Content-Type header media type for test requests.
     *
     * @param string $mediaType
     * @return $this
     */
    protected function withContentMediaType(string $mediaType): self
    {
        $this->contentMediaType = $mediaType;

        return $this;
    }

    /**
     * Get the media type to use for the Content-Type header.
     *
     * @return string
     */
    protected function contentMediaType(): string
    {
        return $this->contentMediaType;
    }

    /**
     * Set the resource type that is expected in the response.
     *
     * @param string $type
     * @return $this
     */
    protected function willSeeResourceType(string $type): self
    {
        $this->expectedResourceType = $type;

        return $this;
    }

    /**
     * Get the resource type that is expected in the response.
     *
     * @return string|null
     */
    protected function expectedResourceType(): ?string
    {
        $expected = $this->expectedResourceType ?: $this->resourceType;

        return $expected ?: null;
    }

    /**
     * @return Api
     * @deprecated 2.0.0
     */
    protected function api()
    {
        return json_api($this->api ?: null);
    }

    /**
     * @param string $url
     * @return $this
     */
    protected function withBaseApiUrl(string $url): self
    {
        $this->baseApiUrl = $url;

        return $this;
    }

    /**
     * @return string
     */
    protected function baseApiUrl(): string
    {
        if (!$this->baseApiUrl) {
            $this->baseApiUrl = $this->api()->getUrl()->getNamespace();
        }

        return $this->prepareUrlForRequest($this->baseApiUrl);
    }

    /**
     * Get a URL for the API being tested.
     *
     * @param mixed ...$extra
     * @return string
     */
    protected function jsonApiUrl(...$extra): string
    {
        return collect([$this->baseApiUrl()])->merge($extra)->map(function ($value) {
            return ($value instanceof UrlRoutable) ? $value->getRouteKey() : $value;
        })->implode('/');
    }

    /**
     * Get a URL for the resource type being tested.
     *
     * @param mixed ...$extra
     * @return string
     */
    protected function resourceUrl(...$extra): string
    {
        array_unshift($extra, $this->resourceType());

        return $this->jsonApiUrl(...$extra);
    }

    /**
     * Add default parameters to those provided to `assertCannot*` method.
     *
     * Classes can override this method if they need to add any default parameters for constructing
     * the route link.
     *
     * @param array $params
     * @return array
     * @deprecated 2.0.0
     */
    protected function addDefaultRouteParams(array $params)
    {
        return $params;
    }

    /**
     * Normalize ids for a find many request
     *
     * @param iterable|UrlRoutable $ids
     * @return array
     * @deprecated 2.0.0
     */
    protected function normalizeIds($ids)
    {
        if ($ids instanceof UrlRoutable) {
            $ids = [$ids];
        }

        return collect($ids)->map(function ($id) {
            return $this->normalizeId($id);
        })->all();
    }

    /**
     * Normalize an id for a resource request.
     *
     * @param $id
     * @return string|int
     * @deprecated 2.0.0
     */
    protected function normalizeId($id)
    {
        return ($id instanceof UrlRoutable) ? $id->getRouteKey() : $id;
    }

    /**
     * @param iterable|null $headers
     * @return array
     * @deprecated 2.0.0 use `jsonApiHeaders()`
     */
    protected function normalizeHeaders(?iterable $headers): array
    {
        return $this->jsonApiHeaders($headers);
    }

    /**
     * @param iterable|null $headers
     * @return array
     */
    protected function jsonApiHeaders(?iterable $headers): array
    {
        return collect([
            'Accept' => $this->acceptMediaType(),
            'CONTENT_TYPE' => $this->contentMediaType(),
        ])->merge($headers)->all();
    }

}
