<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

use Illuminate\Contracts\Routing\UrlRoutable;
use PHPUnit\Framework\Assert;

/**
 * Class MakesJsonApiRequests
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait MakesJsonApiRequests
{

    /**
     * The base URL of the API to test.
     *
     * @var string
     * @deprecated 3.0
     */
    protected $baseApiUrl = '';

    /**
     * The resource type to test.
     *
     * @var string
     * @deprecated 3.0
     */
    protected $resourceType = '';

    /**
     * The resource type expected in the response.
     *
     * @var string
     * @deprecated 3.0
     */
    protected $expectedResourceType = '';

    /**
     * The test request Accept header media type.
     *
     * @var string
     * @deprecated 3.0
     */
    protected $acceptMediaType = '';

    /**
     * The test request content type.
     *
     * @var string
     * @deprecated 3.0
     */
    protected $contentMediaType = '';

    /**
     * Test a JSON API URI.
     *
     * @return TestBuilder
     */
    protected function jsonApi(): TestBuilder
    {
        $builder = new TestBuilder($this);

        if ($expects = $this->expectedResourceType()) {
            $builder->expects($expects);
        }

        if ($accept = $this->acceptMediaType()) {
            $builder->accept($accept);
        }

        if ($contentType = $this->contentMediaType()) {
            $builder->content($this->contentMediaType());
        }

        return $builder;
    }

    /**
     * @param string $uri
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     * @deprecated 3.0 use method chaining from `jsonApi()`.
     */
    protected function getJsonApi(string $uri, iterable $queryParams = [], iterable $headers = []): TestResponse
    {
        return $this
            ->jsonApi()
            ->query($queryParams)
            ->get($uri, $headers);
    }

    /**
     * @param string $uri
     * @param iterable $queryParams
     * @param iterable $data
     * @param iterable $headers
     * @return TestResponse
     * @deprecated 3.0 use method chaining from `jsonApi()`.
     */
    protected function postJsonApi(
        string $uri,
        iterable $queryParams = [],
        iterable $data = [],
        iterable $headers = []
    ): TestResponse
    {
        return $this
            ->jsonApi()
            ->query($queryParams)
            ->content($data)
            ->post($uri, $headers);
    }

    /**
     * @param string $uri
     * @param iterable $queryParams
     * @param iterable $data
     * @param iterable $headers
     * @return TestResponse
     * @deprecated 3.0 use method chaining from `jsonApi()`.
     */
    protected function patchJsonApi(
        string $uri,
        iterable $queryParams = [],
        iterable $data = [],
        iterable $headers = []
    ): TestResponse
    {
        return $this
            ->jsonApi()
            ->query($queryParams)
            ->content($data)
            ->patch($uri, $headers);
    }

    /**
     * @param string $uri
     * @param iterable $queryParams
     * @param iterable $data
     * @param iterable $headers
     * @return TestResponse
     * @deprecated 3.0 use method chaining from `jsonApi()`.
     */
    protected function deleteJsonApi(
        string $uri,
        iterable $queryParams = [],
        iterable $data = [],
        iterable $headers = []
    ): TestResponse
    {
        return $this
            ->jsonApi()
            ->query($queryParams)
            ->content($data)
            ->delete($uri, $headers);
    }

    /**
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     * @deprecated 3.0 use method chaining from `jsonApi()`.
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
     * @deprecated 3.0 use method chaining from `jsonApi()`.
     */
    protected function doSearchById($ids, iterable $queryParams = [], iterable $headers = []): TestResponse
    {
        if ($ids instanceof UrlRoutable) {
            $ids = [$ids];
        }

        $ids = collect($ids)->map(function ($id) {
            return ($id instanceof UrlRoutable) ? $id->getRouteKey() : $id;
        })->all();

        $queryParams['filter'] = $queryParams['filter'] ?? [];
        $queryParams['filter']['id'] = $ids;

        return $this->doSearch($queryParams, $headers);
    }

    /**
     * @param mixed $data
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     * @deprecated 3.0 use method chaining from `jsonApi()`.
     */
    protected function doCreate($data, iterable $queryParams = [], iterable $headers = []): TestResponse
    {
        $data = collect($data)->jsonSerialize();
        $uri = $this->resourceUrl();

        return $this->postJsonApi($uri, $queryParams, compact('data'), $headers);
    }

    /**
     * @param mixed $resourceId
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     * @deprecated 3.0 use method chaining from `jsonApi()`.
     */
    protected function doRead($resourceId, iterable $queryParams = [], iterable $headers = []): TestResponse
    {
        $uri = $this->resourceUrl($resourceId);

        return $this->getJsonApi($uri, $queryParams, $headers);
    }

    /**
     * @param mixed $data
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     * @deprecated 3.0 use method chaining from `jsonApi()`.
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
     * @param mixed $resourceId
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     * @deprecated 3.0 use method chaining from `jsonApi()`.
     */
    protected function doDelete($resourceId, iterable $queryParams = [], iterable $headers = []): TestResponse
    {
        $uri = $this->resourceUrl($resourceId);

        return $this->deleteJsonApi($uri, $queryParams, [], $headers);
    }

    /**
     * @param mixed $resourceId
     * @param string $field
     *      the relationship field name.
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     * @deprecated 3.0 use method chaining from `jsonApi()`.
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
     * @param mixed $resourceId
     * @param string $field
     *      the relationship field name.
     * @param array $queryParams
     * @param array $headers
     * @return TestResponse
     * @deprecated 3.0 use method chaining from `jsonApi()`.
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
     * @param mixed $resourceId
     * @param string $field
     *      the relationship field name.
     * @param mixed $data
     * @param array $queryParams
     * @param array $headers
     * @return TestResponse
     * @deprecated 3.0 use method chaining from `jsonApi()`.
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
     * @param mixed $resourceId
     * @param string $field
     *      the relationship field name.
     * @param mixed $data
     * @param iterable $queryParams
     * @param iterable $headers
     * @return TestResponse
     * @deprecated 3.0 use method chaining from `jsonApi()`.
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
     * @param mixed $resourceId
     * @param string $field
     *      the relationship field name.
     * @param mixed $data
     * @param array $queryParams
     * @param array $headers
     * @return TestResponse
     * @deprecated 3.0 use method chaining from `jsonApi()`.
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
     * Set the resource type to test.
     *
     * @param string $resourceType
     * @return $this
     * @deprecated 3.0
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
     * @deprecated 3.0
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
     * @deprecated 3.0
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
     * @deprecated 3.0
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
     * @deprecated 3.0
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
     * @deprecated 3.0
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
     * @deprecated 3.0
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
     * @deprecated 3.0
     */
    protected function expectedResourceType(): ?string
    {
        $expected = $this->expectedResourceType ?: $this->resourceType;

        return $expected ?: null;
    }

    /**
     * @param string $url
     * @return $this
     * @deprecated 3.0
     */
    protected function withBaseApiUrl(string $url): self
    {
        $this->baseApiUrl = $url;

        return $this;
    }

    /**
     * @return string
     * @deprecated 3.0
     */
    protected function baseApiUrl(): string
    {
        if (!$this->baseApiUrl) {
            $this->baseApiUrl = json_api()->getUrl()->getNamespace();
        }

        return $this->prepareUrlForRequest($this->baseApiUrl);
    }

    /**
     * Get a URL for the API being tested.
     *
     * @param mixed ...$extra
     * @return string
     * @deprecated 3.0
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
     * @deprecated 3.0
     */
    protected function resourceUrl(...$extra): string
    {
        array_unshift($extra, $this->resourceType());

        return $this->jsonApiUrl(...$extra);
    }

}
