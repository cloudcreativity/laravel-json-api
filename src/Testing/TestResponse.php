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

use CloudCreativity\JsonApi\Testing\Concerns\HasHttpAssertions;
use CloudCreativity\JsonApi\Testing\Document;
use Illuminate\Testing\TestResponse as BaseTestResponse;
use Illuminate\Http\Response;

/**
 * Class TestResponse
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class TestResponse extends BaseTestResponse
{

    use HasHttpAssertions;

    /**
     * @param mixed $response
     * @return TestResponse
     */
    public static function cast($response): self
    {
        if ($response instanceof self) {
            return $response;
        }

        if ($response instanceof BaseTestResponse) {
            return new self($response->baseResponse);
        }

        return new self($response);
    }

    /**
     * TestResponse constructor.
     *
     * @param Response $response
     * @param string|null $expectedType
     */
    public function __construct($response, string $expectedType = null)
    {
        parent::__construct($response);

        if ($expectedType) {
            $this->willSeeType($expectedType);
        }
    }

    /**
     * Get the resource ID from the `/data/id` member.
     *
     * @return string|null
     */
    public function id(): ?string
    {
        return $this->getId();
    }

    /**
     * Get the resource ID from the `/data/id` member.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->jsonApi('/data/id');
    }

    /**
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->headers->get('Content-Type');
    }

    /**
     * @return string|null
     */
    public function getContentLocation(): ?string
    {
        return $this->headers->get('Content-Location');
    }

    /**
     * @return string|null
     */
    public function getLocation(): ?string
    {
        return $this->headers->get('Location');
    }

    /**
     * Get the JSON API document or a value from it using a JSON pointer.
     *
     * @param string|null $pointer
     * @return Document|mixed
     */
    public function jsonApi(string $pointer = null)
    {
        $document = $this->getDocument();

        return $pointer ? $document->get($pointer) : $document;
    }

    /**
     * Assert the response is a JSON API page.
     *
     * @param $expected
     * @param array|null $links
     * @param array|null $meta
     * @param string|null $metaKey
     * @param bool $strict
     * @return $this
     */
    public function assertFetchedPage(
        $expected,
        ?array $links,
        ?array $meta,
        string $metaKey = 'page',
        bool $strict = true
    ): self
    {
        $this->assertPage($expected, $links, $meta, $metaKey, $strict);

        return $this;
    }

    /**
     * Assert the response is a JSON API page with expected resources in the specified order.
     *
     * @param $expected
     * @param array|null $links
     * @param array|null $meta
     * @param string|null $metaKey
     * @param bool $strict
     * @return $this
     */
    public function assertFetchedPageInOrder(
        $expected,
        ?array $links,
        ?array $meta,
        string $metaKey = 'page',
        bool $strict = true
    ): self
    {
        $this->assertPage($expected, $links, $meta, $metaKey, $strict, true);

        return $this;
    }

    /**
     * Assert the response is an empty JSON API page.
     *
     * @param array|null $links
     * @param array|null $meta
     * @param string|null $metaKey
     * @param bool $strict
     * @return $this
     */
    public function assertFetchedEmptyPage(
        ?array $links,
        ?array $meta,
        string $metaKey = 'page',
        bool $strict = true
    ): self
    {
        return $this->assertFetchedPage([], $links, $meta, $metaKey, $strict);
    }

    /**
     * Assert that the response has the given status code.
     *
     * @param int $status
     * @return $this
     */
    public function assertStatus($status)
    {
        return $this->assertStatusCode($status);
    }

    /**
     * Assert the response is a JSON API page.
     *
     * @param $expected
     * @param array|null $links
     * @param array|null $meta
     * @param string|null $metaKey
     * @param bool $strict
     * @param bool $order
     * @return void
     */
    private function assertPage(
        $expected,
        ?array $links,
        ?array $meta,
        string $metaKey = 'page',
        bool $strict = true,
        bool $order = false
    ): void
    {
        if (empty($links) && empty($meta)) {
            throw new \InvalidArgumentException('Expecting links or meta to ensure response is a page.');
        }

        if ($order) {
            $this->assertFetchedManyInOrder($expected, $strict);
        } else {
            $this->assertFetchedMany($expected, $strict);
        }

        if ($links) {
            $this->assertLinks($links, $strict);
        }

        if ($meta) {
            $meta = $metaKey ? [$metaKey => $meta] : $meta;
            $this->assertMeta($meta, $strict);
        }
    }
}
