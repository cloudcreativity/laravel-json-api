<?php
/**
 * Copyright 2018 Cloud Creativity Limited
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

use CloudCreativity\JsonApi\Testing\DocumentTester;
use CloudCreativity\JsonApi\Testing\ErrorsTester;
use CloudCreativity\JsonApi\Testing\ResourceObjectsTester;
use CloudCreativity\JsonApi\Testing\ResourceObjectTester;
use CloudCreativity\LaravelJsonApi\Exceptions\InvalidArgumentException;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Foundation\Testing\TestResponse as BaseTestResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface as Keys;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use PHPUnit\Framework\Assert as PHPUnit;
use RuntimeException;

/**
 * Class TestResponse
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class TestResponse extends BaseTestResponse
{

    /**
     * The expected response primary resource type (in the 'data' key).
     *
     * @var string|null
     */
    protected $expectedResourceType;

    /**
     * The expected content type when assert the response has a JSON API document.
     *
     * @var string
     */
    protected $expectedContentType;

    /**
     * @var DocumentTester|null
     */
    private $document;

    /**
     * TestResponse constructor.
     *
     * @param Response $response
     * @param string|null $expectedResourceType
     * @param string $expectedContentType
     */
    public function __construct(
        $response,
        $expectedResourceType = null,
        $expectedContentType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ) {
        parent::__construct($response);
        $this->expectedResourceType = $expectedResourceType;
        $this->expectedContentType = $expectedContentType;
    }

    /**
     * Assert response is a JSON API response.
     *
     * @param int $statusCode
     * @param string|null $contentType
     * @return $this
     * @deprecated
     *      `assertDocument` now checks the content type before decoding the response document.
     */
    public function assertJsonApiResponse(
        $statusCode = Response::HTTP_OK,
        $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ) {
        $this->assertStatus($statusCode);

        if ($contentType) {
            $this->assertHeader('Content-Type', $contentType);
        }

        return $this;
    }

    /**
     * Assert that the response has the given status code.
     *
     * @param int $status
     * @return $this
     */
    public function assertStatus($status)
    {
        $actual = $this->getStatusCode();
        $message = "Expected status code {$status} but received {$actual}";
        $content = (array) json_decode((string) $this->getContent(), true);

        if (isset($content[Keys::KEYWORD_ERRORS])) {
            $message .= " with errors:\n" . json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        PHPUnit::assertSame($status, $actual, $message);

        return $this;
    }

    /**
     * Assert that the response content is a JSON API document.
     *
     * @return DocumentTester
     */
    public function assertDocument()
    {
        if ($this->document) {
            return $this->document;
        }

        $this->assertHeader('Content-Type', $this->expectedContentType);

        return $this->document = DocumentTester::create($this->getContent());
    }

    /**
     * @return $this
     */
    public function assertNoContent()
    {
        $this->assertStatus(Response::HTTP_NO_CONTENT);
        PHPUnit::assertEmpty($this->getContent(), 'Expecting empty body content.');

        return $this;
    }

    /**
     * Assert that the response content contains JSON API errors.
     *
     * @return ErrorsTester
     */
    public function assertErrors()
    {
        return $this->assertDocument()->assertErrors();
    }

    /**
     * Assert the response is the result of searching many resources of the specified type.
     *
     * @param string|string[]|null $resourceType
     *      the expected resource type(s), or null for the expected type set on this tester.
     * @return $this
     */
    public function assertSearchedMany($resourceType = null)
    {
        $this->assertStatus(Response::HTTP_OK)
            ->assertDocument()
            ->assertResourceCollection()
            ->assertTypes($resourceType ?: $this->expectedResourceType());

        return $this;
    }

    /**
     * Assert the response is the result of searching many resource and contains multiple types.
     *
     * @param array $expected
     * @return $this
     */
    public function assertSearchedPolymorphMany(array $expected)
    {
        $this->assertStatus(Response::HTTP_OK)
            ->assertDocument()
            ->assertResourceCollection()
            ->assertTypes($expected);

        return $this;
    }

    /**
     * Assert the response is the result of searching for many resources, but none were found.
     *
     * @return $this
     */
    public function assertSearchedNone()
    {
        $this->assertStatus(Response::HTTP_OK)
            ->assertDocument()
            ->assertResourceCollection()
            ->assertEmpty();

        return $this;
    }

    /**
     * Assert the data member contains the expected resource, or null.
     *
     * @param array|null $expected
     *      the expected resource, or null.
     * @return $this
     */
    public function assertSearchedOne($expected)
    {
        if (!is_null($expected)) {
            $this->assertRead($expected);
        } else {
            $this->assertStatus(Response::HTTP_OK)->assertDocument()->assertDataNull();
        }

        return $this;
    }

    /**
     * Assert the response contains the results of a search by ids for the expected resource type.
     *
     * @param mixed $expectedIds
     *      the ids that are expected to be in the response.
     * @param string|null $resourceType
     *      the expected resource type, or null for the expected type set on this tester.
     * @return $this
     */
    public function assertSearchedIds($expectedIds, $resourceType = null)
    {
        $resourceType = $resourceType ?: $this->expectedResourceType();

        $expected = collect($this->normalizeIds($expectedIds))->map(function ($id) use ($resourceType) {
            return ['type' => $resourceType, 'id' => $id];
        });

        $this->assertStatus(Response::HTTP_OK)
            ->assertDocument()
            ->assertResourceCollection()
            ->assertContainsExact($expected->all());

        return $this;
    }

    /**
     *
     * The expected values must be keyed by resource type, and contain either a string id or an array
     * of string ids per type. For example:
     *
     * `['posts' => ['1', '2'], 'comments' => '1']`
     *
     * You can pass URL routable objects e.g. models as the values, e.g.:
     *
     * `['posts' => [$post1, $post2], 'comments' => $comment]`
     *
     * @param array $expected
     * @return $this
     */
    public function assertSearchedPolymorphIds(array $expected)
    {
        $expected = collect($expected)->map(function ($value) {
            return $this->normalizeIds($value);
        })->all();

        $this->assertStatus(Response::HTTP_OK)
            ->assertDocument()
            ->assertResourceCollection()
            ->assertContainsOnly($expected);

        return $this;
    }

    /**
     * Assert response is a JSON API resource created response.
     *
     * @param array $expected
     *      array representation of the expected attributes of the resource.
     * @return $this
     */
    public function assertCreated(array $expected = [])
    {
        if (!isset($expected['type'])) {
            $expected['type'] = $this->expectedResourceType();
        }

        $this->assertStatus(Response::HTTP_CREATED)
            ->assertHeader('Location')
            ->assertDocument()
            ->assertResource()
            ->assertMatches($expected);

        return $this;
    }

    /**
     * Assert response is a JSON API resource created response, and return the created id.
     *
     * @param array $expected
     * @return string
     */
    public function assertCreatedWithId(array $expected = [])
    {
        $this->assertCreated($expected);
        $id = array_get($this->decodeResponseJson(), 'data.id');

        PHPUnit::assertNotEmpty($id, 'Create response does not include a valid id.');
        PHPUnit::assertInternalType('string', $id);

        return $id;
    }

    /**
     * Assert response is a JSON API read resource response.
     *
     * @param array $expected
     *      array representation of the expected attributes of the resource.
     * @return $this
     */
    public function assertRead(array $expected)
    {
        if (!isset($expected['type'])) {
            $expected['type'] = $this->expectedResourceType();
        }

        $this->assertStatus(Response::HTTP_OK)
            ->assertDocument()
            ->assertResource()
            ->assertMatches($expected);

        return $this;
    }

    /**
     * Assert response is a JSON API resource updated response.
     *
     * @param array $expected
     *      array representation of the expected resource, or null for a no-content response
     * @return $this
     */
    public function assertUpdated(array $expected = null)
    {
        if (is_null($expected)) {
            $this->assertNoContent();
        } else {
            $this->assertRead($expected);
        }

        return $this;
    }

    /**
     * Assert response is a JSON API resource deleted response.
     *
     * The JSON API spec says that:
     *
     * - A server MUST return a 204 No Content status code if a deletion request is successful
     * and no content is returned.
     * - A server MUST return a 200 OK status code if a deletion request is successful and the server responds
     * with only top-level meta data.
     *
     * @param array|null $expected
     *      the expected top-level meta, or null for no content response.
     * @return $this
     */
    public function assertDeleted(array $expected = null)
    {
        if (is_null($expected)) {
            $this->assertStatus(Response::HTTP_NO_CONTENT);
        } else {
            $this->assertStatus(Response::HTTP_OK);
            // @todo assert top-level meta
        }

        return $this;
    }

    /**
     * Assert response is a JSON API asynchronous process response.
     *
     * @param array|null $expected
     *      the expected asynchronous process resource object.
     * @param string|null $location
     *      the expected location for the asynchronous process resource object, without the id.
     * @return $this
     */
    public function assertAccepted(array $expected = null, string $location = null)
    {
        $this->assertStatus(Response::HTTP_ACCEPTED);

        if ($location && $id = $this->json('data.id')) {
            $location = "{$location}/{$id}";
        }

        $this->assertHeader('Content-Location', $location);

        if ($expected) {
            $this->assertJson(['data' => $expected]);
        }

        return $this;
    }

    /**
     * Assert response is a has-one related resource response.
     *
     * @param array $expected
     *      array representation of the expected resource, or null if null is expected.
     * @return $this
     */
    public function assertReadHasOne(array $expected = null)
    {
        if (is_array($expected) && !isset($expected['type'])) {
            throw new InvalidArgumentException('Expected related resource must have a resource type.');
        }

        return $this->assertSearchedOne($expected);
    }

    /**
     * Assert response is a has-one related resource identifier response.
     *
     * @param string|null $resourceType
     *      the resource type, or null if the data member is expected to be null.
     * @param string|null $resourceId
     * @return $this
     */
    public function assertReadHasOneIdentifier($resourceType, $resourceId = null)
    {
        $document = $this->assertStatus(Response::HTTP_OK)->assertDocument();

        if (is_null($resourceType)) {
            $document->assertDataNull();
        } else {
            $document->assertResourceIdentifier()->assertIs($resourceType, $this->normalizeId($resourceId));
        }

        return $this;
    }

    /**
     * Assert response is a has-many related resource response.
     *
     * @param string|null $resourceType
     *      string expected resource type, or null for an empty relationship.
     * @param mixed|null $expectedIds
     *      the expected specific ids.
     * @return $this
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
     * Assert response is a has-many polymorphic related resources response.
     *
     * The expected values must be keyed by resource type, and contain either a string id or an array
     * of string ids per type. For example:
     *
     * `['posts' => ['1', '2'], 'videos' => '1']`
     *
     * or
     *
     * `['posts' => $posts, 'videos' => $video]`
     *
     * An empty array indicates you expect the relationship to be empty.
     *
     * @param array $expected
     * @return $this
     */
    public function assertReadPolymorphHasMany($expected)
    {
        if (empty($expected)) {
            $this->assertSearchedNone();
            return $this;
        }

        $expected = collect($expected)->map(function ($ids) {
            return $this->normalizeIds($ids);
        })->all();

        $this->assertStatus(Response::HTTP_OK)
            ->assertDocument()
            ->assertResourceCollection()
            ->assertContainsOnly($expected);

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
     */
    public function assertReadHasManyIdentifiers($resourceType, $expectedIds = null)
    {
        if (is_null($resourceType)) {
            $this->assertSearchedNone();
        } elseif (!is_null($expectedIds)) {
            // @todo this checks for resources, not identifiers.
            $this->assertSearchedIds($expectedIds, $resourceType);
        } else {
            // @todo this checks for resources, not identifiers.
            $this->assertSearchedMany($resourceType);
        }

        return $this;
    }

    /**
     * Assert the response is a polymorphic has-many related resource identifiers response.
     *
     * @param array|null $expected
     *      the list of resource types that are expected in the relationship, keyed by resource type.
     * @return $this
     */
    public function assertReadPolymorphHasManyIdentifiers($expected)
    {
        if (empty($expected)) {
            $this->assertSearchedNone();
        } else {
            // @todo this checks for resources, not identifiers.
            $this->assertSearchedPolymorphIds($expected);
        }

        return $this;
    }

    /**
     * Assert a response with a singular resource in the `data` member.
     *
     * @param $resourceId
     * @param string $contentType
     * @return $this
     * @deprecated
     */
    public function assertResourceResponse($resourceId, $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE)
    {
        if ($resourceId instanceof UrlRoutable) {
            $resourceId = $resourceId->getRouteKey();
        }

        $this->assertJsonApiResponse(Response::HTTP_OK, $contentType);
        $this->assertDataResource([
            Keys::KEYWORD_TYPE => $this->expectedResourceType(),
            Keys::KEYWORD_ID => $resourceId,
        ]);

        return $this;
    }

    /**
     * Assert a response with a collection of resources of the expected type in the `data` member.
     *
     * @param string $contentType
     * @return $this
     * @deprecated
     */
    public function assertResourcesResponse($contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE)
    {
        $this->assertJsonApiResponse(Response::HTTP_OK, $contentType);
        $this->assertDataCollection($this->expectedResourceType());

        return $this;
    }

    /**
     * Assert a response with a collection of resources of the expected type in the `data` member.
     *
     * @param string|string[]
     * @param string $contentType
     * @return $this
     * @deprecated
     */
    public function assertRelatedResourcesResponse(
        $expectedTypes,
        $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ) {
        $this->assertJsonApiResponse(Response::HTTP_OK, $contentType);
        $this->assertDataCollection($expectedTypes);

        return $this;
    }

    /**
     * Assert that a search response is a collection only containing the expected resource type.
     *
     * @param string $contentType
     * @return ResourceObjectsTester
     * @deprecated
     */
    public function assertSearchResponse($contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE)
    {
        $this->assertJsonApiResponse(Response::HTTP_OK, $contentType);

        return $this
            ->assertDocument()
            ->assertResourceCollection()
            ->assertTypes($this->expectedResourceType());
    }

    /**
     * Assert that a search response contains a singleton resource with the expected id.
     *
     * @param string|int|UrlRoutable $expectedId
     * @param string $contentType
     * @return ResourceObjectTester
     * @deprecated
     */
    public function assertSearchOneResponse($expectedId, $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE)
    {
        if ($expectedId instanceof UrlRoutable) {
            $expectedId = $expectedId->getRouteKey();
        }

        $this->assertJsonApiResponse(Response::HTTP_OK, $contentType);

        return $this
            ->assertDocument()
            ->assertResource()
            ->assertIs($this->expectedResourceType(), $expectedId);
    }

    /**
     * Assert response is a JSON API resource created response.
     *
     * @param array $expected
     *      array representation of the expected attributes of the resource.
     * @param string $contentType
     * @return string
     *      the id of the created resource.
     * @deprecated
     */
    public function assertCreateResponse(
        array $expected,
        $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ) {
        $this->assertJsonApiResponse(Response::HTTP_CREATED, $contentType)
            ->assertDataResource($expected);

        $data = $this->decodeResponseJson()[Keys::KEYWORD_DATA];
        $id = $data[Keys::KEYWORD_ID];
        $this->assertHeader('Location');

        return $id;
    }

    /**
     * Assert response is a JSON API read resource response.
     *
     * @param array $expected
     *      array representation of the expected attributes of the resource.
     * @param string $contentType
     * @return $this
     * @deprecated
     */
    public function assertReadResponse(array $expected, $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE)
    {
        $this->assertJsonApiResponse(Response::HTTP_OK, $contentType)->assertDataResource($expected);

        return $this;
    }

    /**
     * Assert response is a JSON API resource updated response.
     *
     * @param array $expected
     *      array representation of the expected attributes of the resource.
     * @param string $contentType
     * @return $this
     * @deprecated
     */
    public function assertUpdateResponse(array $expected, $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE)
    {
        $this->assertJsonApiResponse(Response::HTTP_OK, $contentType)->assertDataResource($expected);

        return $this;
    }

    /**
     * Assert response is a JSON API resource deleted response.
     *
     * @param int $statusCode
     * @param string $contentType
     *      the content type if content type is expected (i.e. ignored for 204 responses).
     * @return $this
     * @deprecated
     */
    public function assertDeleteResponse(
        $statusCode = Response::HTTP_NO_CONTENT,
        $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ) {
        if (Response::HTTP_NO_CONTENT == $statusCode) {
            $this->assertStatus(Response::HTTP_NO_CONTENT);
        } else {
            $this->assertJsonApiResponse($statusCode, $contentType);
        }

        return $this;
    }

    /**
     * Assert response is a JSON API read resource response.
     *
     * @param array $expected
     *      array representation of the expected attributes of the resource.
     * @param string $contentType
     * @return $this
     * @deprecated
     */
    public function assertRelatedResourceResponse(
        array $expected,
        $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ) {
        $this->assertJsonApiResponse(Response::HTTP_OK, $contentType)
            ->assertDataResource($expected);

        return $this;
    }

    /**
     * @param string|string[] $resourceType
     * @param mixed $id
     *      the id, or null if no identifier is expected in the response.
     * @param string $contentType
     * @return $this
     * @deprecated
     */
    public function assertHasOneRelationshipResponse(
        $resourceType,
        $id = null,
        $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ) {
        $this->assertJsonApiResponse(Response::HTTP_OK, $contentType)
            ->assertDataResourceIdentifier($resourceType, $id);

        return $this;
    }

    /**
     * Assert that the response has the given status code and output the response if not.
     *
     * @param $expected
     * @return $this
     * @deprecated use `assertStatus`
     */
    public function assertStatusCode($expected)
    {
        $this->assertStatus($expected);

        return $this;
    }

    /**
     * See that there is a collection of resources as primary data.
     *
     * @param string|string[] $resourceType
     * @param bool $allowEmpty
     * @return $this
     * @deprecated
     */
    public function assertDataCollection($resourceType = null, $allowEmpty = true)
    {
        $resources = $this->assertDocument()->assertResourceCollection();

        if (!$allowEmpty) {
            $resources->assertNotEmpty();
        }

        if (!$resources->isEmpty()) {
            $resources->assertTypes($resourceType ?: $this->expectedResourceType());
        }

        return $this;
    }

    /**
     * See that there is a resource object as primary data.
     *
     * @param array $expected
     *      the expected array representation of the resource.
     * @return $this
     * @deprecated
     */
    public function assertDataResource(array $expected)
    {
        /** If no type in the expected data, add our expected resource type. */
        if (!isset($expected[Keys::KEYWORD_TYPE])) {
            $expected[Keys::KEYWORD_TYPE] = $this->expectedResourceType();
        }

        $this->assertDocument()->assertResource()->assertMatches($expected);

        return $this;
    }

    /**
     * @param string|string[]|null $resourceType
     *      if null, will use the expected resource type.
     * @param mixed $id
     *      the expected id in the identifier, or null if no identifier is expected.
     * @return $this
     * @deprecated
     */
    public function assertDataResourceIdentifier($resourceType = null, $id = null)
    {
        $document = $this->assertDocument();

        if (is_null($id)) {
            $document->assertDataNull();
        } else {
            $document->assertResourceIdentifier()->assertIs($resourceType ?: $this->expectedResourceType(), $id);
        }

        return $this;
    }

    /**
     * Assert that the response to a search by id(s) request contains the expected ids.
     *
     * @param array|Collection|UrlRoutable $expectedIds
     *      the ids - may contain UrlRoutable objects (e.g. Models)
     * @param string $contentType
     * @return ResourceObjectsTester
     * @deprecated
     */
    public function assertSearchByIdResponse($expectedIds, $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE)
    {
        $this->assertJsonApiResponse(Response::HTTP_OK, $contentType);

        return $this
            ->assertDocument()
            ->assertResourceCollection()
            ->assertContainsOnly([
                $this->expectedResourceType() => $this->normalizeIds($expectedIds),
            ]);
    }

    /**
     * @return string
     */
    protected function expectedResourceType()
    {
        if (!$this->expectedResourceType) {
            throw new RuntimeException('No expected resource type set on the test response helper.');
        }

        return $this->expectedResourceType;
    }

    /**
     * Normalize ids for a find many request
     *
     * @param array|Collection|UrlRoutable $ids
     * @return array
     */
    protected function normalizeIds($ids)
    {
        if ($ids instanceof UrlRoutable) {
            $ids = [$ids];
        }

        return collect($ids)->map(function ($id) {
            return $this->normalizeId($id);
        })->values()->all();
    }

    /**
     * @param $id
     * @return mixed
     */
    protected function normalizeId($id)
    {
        $id = ($id instanceof UrlRoutable) ? $id->getRouteKey() : $id;

        return (string) $id;
    }

}
