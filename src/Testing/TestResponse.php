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

use CloudCreativity\JsonApi\Testing\Compare;
use CloudCreativity\JsonApi\Testing\Concerns\HasHttpAssertions;
use CloudCreativity\JsonApi\Testing\Document;
use Illuminate\Foundation\Testing\TestResponse as BaseTestResponse;
use Illuminate\Http\Response;
use PHPUnit\Framework\Assert;

/**
 * Class TestResponse
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class TestResponse extends BaseTestResponse
{

    use HasHttpAssertions;

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
     * @param string[]|string|null $expected
     * @return $this
     * @deprecated 2.0.0 use assertFetchedMany() or assertFetchManyInOrder()
     */
    public function assertFetchedManyTypes($expected = null): self
    {
        $this->assertStatus(Response::HTTP_OK);

        $expected = collect(
            empty($expected) ? $this->getExpectedType() : (array) $expected
        )->unique()->sort()->values();

        $actual = collect((array) $this->jsonApi('/data'))->map(function ($value) {
            $value = Compare::hash($value) ? $value : [];

            return $value['type'] ?? null;
        })->unique()->sort()->values();

        Assert::assertEquals($expected->all(), $actual->all());

        return $this;
    }

    /**
     * Assert the response is the result of searching many resources of the specified type.
     *
     * @param string|string[]|null $resourceType
     *      the expected resource type(s), or null for the expected type set on this tester.
     * @return $this
     * @deprecated 2.0.0 use assertFetchedMany() or assertFetchManyInOrder()
     */
    public function assertSearchedMany($resourceType = null)
    {
        return $this->assertFetchedManyTypes($resourceType);
    }

    /**
     * Assert the response is the result of searching many resource and contains multiple types.
     *
     * @param array $expected
     * @return $this
     * @deprecated 2.0.0 use assertFetchedMany() or assertFetchManyInOrder()
     */
    public function assertSearchedPolymorphMany(array $expected)
    {
        return $this->assertFetchedManyTypes($expected);
    }

    /**
     * Assert the response is the result of searching for many resources, but none were found.
     *
     * @return $this
     * @deprecated 2.0.0 use `assertFetchedNone`.
     */
    public function assertSearchedNone()
    {
        return $this->assertFetchedNone();
    }

    /**
     * Assert the data member contains the expected resource, or null.
     *
     * @param array|null $expected
     *      the expected resource, or null.
     * @return $this
     * @deprecated 2.0.0 use `assertFetchedOne` or `assertFetchedNull`
     */
    public function assertSearchedOne($expected)
    {
        if (is_null($expected)) {
            return $this->assertFetchedNull();
        }

        return $this->assertFetchedOne($expected);
    }

    /**
     * Assert the response contains the results of a search by ids for the expected resource type.
     *
     * @param mixed $expectedIds
     *      the ids that are expected to be in the response.
     * @param string|null $resourceType
     *      the expected resource type, or null for the expected type set on this tester.
     * @return $this
     * @deprecated 2.0.0 use `assertFetchedMany`
     */
    public function assertSearchedIds($expectedIds, $resourceType = null)
    {
        if ($resourceType) {
            $this->willSeeType($resourceType);
        }

        return $this->assertFetchedMany($expectedIds);
    }

    /**
     * Assert response is a JSON API resource created response.
     *
     * @param array $expected
     *      array representation of the expected attributes of the resource.
     * @return $this
     * @deprecated 2.0.0 use `assertCreatedWithServerId` or `assertCreatedWithClientId`.
     */
    public function assertCreated(array $expected = [])
    {
        $expected['type'] = $expected['type'] ?? $this->getExpectedType();

        $this->assertStatus(Response::HTTP_CREATED)
            ->assertHeader('Location')
            ->getDocument()
            ->assertHash($expected);

        return $this;
    }

    /**
     * Assert response is a JSON API resource created response, and return the created id.
     *
     * @param array $expected
     * @return string
     * @deprecated 2.0.0 use `assertCreatedWithServerId`
     */
    public function assertCreatedWithId(array $expected = []): string
    {
        $this->assertCreated($expected);
        $id = $this->getId();

        Assert::assertNotEmpty($id, 'Create response does not include a valid id.');

        // Assert::assertIsString does not exist in PHPUnit < 7.5 but
        // Assert::assertInternalType is deprecated in PHPUnit >= 8.0
        if (method_exists(Assert::class, 'assertIsString')) {
            Assert::assertIsString($id);
        } else {
            Assert::assertInternalType('string', $id);
        }

        return $id;
    }

    /**
     * Assert response is a JSON API read resource response.
     *
     * @param array $expected
     *      array representation of the expected attributes of the resource.
     * @return $this
     * @deprecated 2.0.0 use `assertFetchedOne`
     */
    public function assertRead(array $expected)
    {
        return $this->assertFetchedOne($expected);
    }

    /**
     * Assert response is a has-one related resource response.
     *
     * @param array $expected
     *      array representation of the expected resource, or null if null is expected.
     * @return $this
     * @deprecated 2.0.0 use `assertFetchedNull` or `assertFetchedOne`.
     */
    public function assertReadHasOne(array $expected = null)
    {
        if (is_null($expected)) {
            return $this->assertFetchedNull();
        }

        if (!isset($expected['type'])) {
            throw new \InvalidArgumentException('Expected related resource must have a resource type.');
        }

        return $this->assertFetchedOne($expected);
    }

    /**
     * Assert response is a has-one related resource identifier response.
     *
     * @param string|null $resourceType
     *      the resource type, or null if the data member is expected to be null.
     * @param string|null $resourceId
     * @return $this
     * @deprecated 2.0.0 use `assertFetchedNull` or `assertFetchedToOne`
     */
    public function assertReadHasOneIdentifier($resourceType, $resourceId = null)
    {
        if (is_null($resourceType)) {
            return $this->assertFetchedNull();
        }

        return $this->willSeeType($resourceType)->assertFetchedToOne($resourceId);
    }

    /**
     * Assert response is a has-many related resource response.
     *
     * @param string|null $resourceType
     *      string expected resource type, or null for an empty relationship.
     * @param mixed|null $expectedIds
     *      the expected specific ids.
     * @return $this
     * @deprecated 2.0.0 use `assertFetchedNone` or `assertFetchedMany`.
     */
    public function assertReadHasMany($resourceType, $expectedIds = null)
    {
        if (is_null($resourceType)) {
            $this->assertSearchedNone();
        } elseif (!is_null($expectedIds)) {
            $this->assertSearchedIds($expectedIds, $resourceType);
        } else {
            $this->assertSearchedMany($resourceType);
        }

        return $this;
    }

    /**
     * Assert response is a has-many related resource identifiers response.
     *
     * @param string|null $resourceType
     *      string expected resource type, or null for an empty relationship.
     * @param mixed|null $expectedIds
     *      the expected specific ids.
     * @return $this
     * @deprecated 2.0.0 use `assertFetchedNone` or `assertFetchedToMany`
     */
    public function assertReadHasManyIdentifiers($resourceType, $expectedIds = null)
    {
        if (is_null($resourceType)) {
            $this->assertSearchedNone();
        } elseif (!is_null($expectedIds)) {
            $this->assertSearchedIds($expectedIds, $resourceType);
        } else {
            $this->assertSearchedMany($resourceType);
        }

        return $this;
    }

    /**
     * @return string
     * @deprecated 2.0.0 use `getExpectedType`
     */
    protected function expectedResourceType()
    {
        return $this->getExpectedType();
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
