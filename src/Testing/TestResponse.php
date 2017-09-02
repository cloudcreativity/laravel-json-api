<?php

namespace CloudCreativity\LaravelJsonApi\Testing;

use CloudCreativity\JsonApi\Testing\DocumentTester;
use CloudCreativity\JsonApi\Testing\ErrorsTester;
use CloudCreativity\JsonApi\Testing\ResourceObjectsTester;
use CloudCreativity\JsonApi\Testing\ResourceObjectTester;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Foundation\Testing\TestResponse as BaseTestResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface as Keys;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use RuntimeException;

class TestResponse extends BaseTestResponse
{

    /**
     * The expected response primary resource type (in the 'data' key).
     *
     * @var string|null
     */
    protected $expectedResourceType;

    /**
     * TestResponse constructor.
     *
     * @param Response $response
     * @param string|null $expectedResourceType
     */
    public function __construct($response, $expectedResourceType = null)
    {
        parent::__construct($response);
        $this->expectedResourceType = $expectedResourceType;
    }

    /**
     * Assert response is a JSON API response.
     *
     * @param int $statusCode
     * @param string|null $contentType
     * @return $this
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
     * Assert a response with a singular resource in the `data` member.
     *
     * @param $resourceId
     * @param string $contentType
     * @return $this
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
     * @todo needs to support `null` responses
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
     * @return DocumentTester
     */
    public function assertDocument()
    {
        return DocumentTester::create($this->baseResponse->getContent());
    }

    /**
     * @return ErrorsTester
     */
    public function assertErrors()
    {
        return $this->assertDocument()->assertErrors();
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
            return ($id instanceof UrlRoutable) ? $id->getRouteKey() : $id;
        })->all();
    }

}
