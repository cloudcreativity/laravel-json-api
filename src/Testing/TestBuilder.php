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

use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use function array_walk_recursive;
use function collect;
use function implode;
use function is_bool;
use function is_null;
use function is_scalar;

final class TestBuilder
{

    /**
     * @var TestCase|MakesHttpRequests
     */
    private $test;

    /**
     * @var string
     */
    private $accept;

    /**
     * @var string
     */
    private $contentType;

    /**
     * @var string|null
     */
    private $expectedResourceType;

    /**
     * @var Collection
     */
    private $query;

    /**
     * @var Collection
     */
    private $headers;

    /**
     * @var Collection|null
     */
    private $json;

    /**
     * @var Collection|null
     */
    private $payload;

    /**
     * TestBuilder constructor.
     *
     * @param mixed $test
     */
    public function __construct($test)
    {
        $this->test = $test;
        $this->accept = $this->contentType = 'application/vnd.api+json';
        $this->query = collect();
        $this->headers = collect();
    }

    /**
     * Set the resource type that is expected in the response body.
     *
     * @param string $resourceType
     * @return $this
     */
    public function expects(string $resourceType): self
    {
        $this->expectedResourceType = $resourceType;

        return $this;
    }

    /**
     * Set the accept media type for the request.
     *
     * @param string|null $mediaType
     * @return $this
     */
    public function accept(?string $mediaType): self
    {
        $this->accept = $mediaType;

        return $this;
    }

    /**
     * Set the content media type for the request.
     *
     * @param string|null $mediaType
     * @return $this
     */
    public function contentType(?string $mediaType): self
    {
        $this->contentType = $mediaType;

        return $this;
    }

    /**
     * Set the request content type to 'application/x-www-form-urlencoded'.
     *
     * @return $this
     */
    public function asFormUrlEncoded(): self
    {
        return $this->contentType('application/x-www-form-urlencoded');
    }

    /**
     * Set the request content type to multipart form data.
     *
     * @return $this
     */
    public function asMultiPartFormData(): self
    {
        return $this->contentType(
            'multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW'
        );
    }

    /**
     * Add query parameters to the request.
     *
     * @param iterable $query
     * @return $this
     */
    public function query(iterable $query): self
    {
        $this->query = collect($query)->merge($query);

        return $this;
    }

    /**
     * Set the include paths.
     *
     * @param string ...$paths
     * @return $this
     */
    public function includePaths(string ...$paths): self
    {
        $this->query['include'] = implode(',', $paths);

        return $this;
    }

    /**
     * Set the sparse field sets for a resource type.
     *
     * @param string $resourceType
     * @param string|string[] $fieldNames
     * @return $this
     */
    public function sparseFields(string $resourceType, $fieldNames): self
    {
        $this->query['fields'] = collect($this->query->get('fields'))
            ->put($resourceType, implode(',', Arr::wrap($fieldNames)));

        return $this;
    }

    /**
     * Set the filter parameters.
     *
     * @param iterable $filter
     * @return $this
     */
    public function filter(iterable $filter): self
    {
        $this->query['filter'] = collect($filter);

        return $this;
    }

    /**
     * Set the sort parameters.
     *
     * @param string ...$sort
     * @return $this
     */
    public function sort(string ...$sort): self
    {
        $this->query['sort'] = implode(',', $sort);

        return $this;
    }

    /**
     * Set the pagination parameters.
     *
     * @param iterable $page
     * @return $this
     */
    public function page(iterable $page): self
    {
        $this->query['page'] = collect($page);

        return $this;
    }

    /**
     * Set the data member of the request JSON API document.
     *
     * @param iterable|null $data
     * @return $this
     * @deprecated 4.0 use `withData`.
     */
    public function data($data): self
    {
        return $this->withData($data);
    }

    /**
     * Set the data member of the request JSON API document.
     *
     * @param iterable|null $data
     * @return $this
     */
    public function withData($data): self
    {
        if (is_null($data)) {
            return $this->withJson(['data' => null]);
        }

        return $this->withJson(['data' => collect($data)]);
    }

    /**
     * Set the JSON request document.
     *
     * @param $json
     * @return $this
     */
    public function withJson($json): self
    {
        $this->json = collect($json);

        return $this;
    }

    /**
     * Set the request JSON API document (HTTP request body).
     *
     * @param mixed $document
     * @param string|null $contentType
     * @return $this
     * @deprecated 4.0
     */
    public function content($document, string $contentType = null): self
    {
        $this->json = collect($document);

        if ($contentType) {
            $this->contentType($contentType);
        }

        return $this;
    }

    /**
     * Set the request payload for a non-JSON API request.
     *
     * @param $parameters
     * @return $this
     */
    public function withPayload($parameters): self
    {
        $this->payload = collect($parameters);
        // we need a content length as it is used by the JSON API implementation to determine if there is body.
        $this->headers['CONTENT_LENGTH'] = '1';

        return $this;
    }

    /**
     * @param iterable $headers
     * @return $this
     */
    public function withHeaders(iterable $headers): self
    {
        $this->headers = $this->headers->merge($headers);

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function withHeader(string $name, string $value): self
    {
        $this->headers->put($name, $value);

        return $this;
    }

    /**
     * Visit the given URI with a GET request, expecting JSON API content.
     *
     * @param string $uri
     * @param iterable $headers
     * @return TestResponse
     */
    public function get(string $uri, iterable $headers = []): TestResponse
    {
        return $this->call('GET', $uri, $headers);
    }

    /**
     * Visit the given URI with a POST request, expecting JSON API content.
     *
     * @param string $uri
     * @param iterable $headers
     * @return TestResponse
     */
    public function post(string $uri, iterable $headers = []): TestResponse
    {
        return $this->call('POST', $uri, $headers);
    }

    /**
     * Visit the given URI with a PATCH request, expecting JSON API content.
     *
     * @param string $uri
     * @param iterable $headers
     * @return TestResponse
     */
    public function patch(string $uri, iterable $headers = []): TestResponse
    {
        return $this->call('PATCH', $uri, $headers);
    }

    /**
     * @param string $uri
     * @param array|iterable $headers
     * @return TestResponse
     */
    public function put(string $uri, iterable $headers = []): TestResponse
    {
        return $this->call('PUT', $uri, $headers);
    }

    /**
     * Visit the given URI with a DELETE request, expecting JSON API content.
     *
     * @param string $uri
     * @param iterable $headers
     * @return TestResponse
     */
    public function delete(string $uri, iterable $headers = []): TestResponse
    {
        return $this->call('DELETE', $uri, $headers);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param iterable $headers
     * @return TestResponse
     */
    public function call(string $method, string $uri, iterable $headers = []): TestResponse
    {
        if ($this->query->isNotEmpty()) {
            $uri .= '?' . $this->buildQuery();
        }

        $headers = $this->buildHeaders($headers);

        if ($this->payload) {
            $response = $this->test->{strtolower($method)}(
                $uri,
                $this->payload->toArray(),
                $headers
            );
        } else {
            $response = $this->test->json(
                $method,
                $uri,
                $this->json ? $this->json->toArray() : [],
                $headers
            );
        }

        $response = TestResponse::cast($response);

        if ($this->expectedResourceType) {
            $response->willSeeResourceType($this->expectedResourceType);
        }

        return $response;
    }

    /**
     * Convert query params to a string.
     *
     * We check all values are strings, integers or floats as these are the only
     * valid values that can be sent in the query params. E.g. if the developer
     * uses a `boolean`, they actually need to test where the strings `'true'`
     * or `'false'` (or the string/integer equivalents) work.
     *
     * @return string
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/427
     */
    private function buildQuery(): string
    {
        $query = $this->query->toArray();

        array_walk_recursive($query, function ($value, $key) {
            if (!is_scalar($value) || is_bool($value)) {
                Assert::fail("Test query parameter at {$key} is not a string, integer or float.");
            }
        });

        return Arr::query($query);
    }

    /**
     * @param iterable $headers
     * @return array
     */
    private function buildHeaders(iterable $headers): array
    {
        return collect(['Accept' => $this->accept, 'CONTENT_TYPE' => $this->contentType])
            ->filter()
            ->merge($this->headers)
            ->merge($headers)
            ->toArray();
    }
}
