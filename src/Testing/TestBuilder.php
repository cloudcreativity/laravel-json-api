<?php

namespace CloudCreativity\LaravelJsonApi\Testing;

use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use function array_walk_recursive;
use function collect;
use function http_build_query;
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
    private $document;

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
        $this->document = collect();
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
     * Set the sparse fieldsets for a resource type.
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
     * @param mixed|null $data
     * @return $this
     */
    public function data($data): self
    {
        if (is_null($data)) {
            $this->document->put('data', null);
        } else {
            $this->document->put('data', collect($data));
        }

        return $this;
    }

    /**
     * Set the request JSON API document (HTTP request body).
     *
     * @param mixed $document
     * @param string|null $contentType
     * @return $this
     */
    public function content($document, string $contentType = null): self
    {
        $this->document = collect($document);

        if ($contentType) {
            $this->contentType($contentType);
        }

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

        $headers = collect([
            'Accept' => $this->accept,
            'CONTENT_TYPE' => $this->contentType,
        ])->filter()->merge($headers);

        $response = TestResponse::cast($this->test->json(
            $method,
            $uri,
            $this->document->toArray(),
            $headers->toArray()
        ));

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

        return http_build_query($query);
    }
}
