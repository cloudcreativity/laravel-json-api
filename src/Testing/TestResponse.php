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
use PHPUnit_Framework_Assert as PHPUnit;

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
        $this->assertStatusCode($statusCode);

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
            $this->assertStatusCode(Response::HTTP_NO_CONTENT);
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
     */
    public function assertStatusCode($expected)
    {
        $actual = $this->baseResponse->getStatusCode();
        $message = "Expected status code {$expected}, got {$actual}";
        $content = (array) json_decode((string) $this->baseResponse->getContent(), true);

        if (isset($content[Keys::KEYWORD_ERRORS])) {
            $message .= " with errors:\n" . json_encode($content, JSON_PRETTY_PRINT);
        }

        PHPUnit::assertEquals($expected, $actual, $message);

        return $this;
    }

    /**
     * @param $expected
     * @return TestResponse
     * @deprecated use `assertStatusCode`
     */
    public function seeStatusCode($expected)
    {
        return $this->assertStatusCode($expected);
    }

    /**
     * See that there is a collection of resources as primary data.
     *
     * @param string|string[] $resourceType
     * @param bool $allowEmpty
     * @return $this
     */
    public function assertDataCollection($resourceType, $allowEmpty = true)
    {
        $this->assertJsonStructure([
            Keys::KEYWORD_DATA,
        ]);

        $collection = $this->decodeResponseJson()[Keys::KEYWORD_DATA];

        if (!$allowEmpty) {
            PHPUnit::assertNotEmpty($collection, 'Data collection is empty');
        } elseif (empty($collection)) {
            return $this;
        }

        $expected = array_combine((array) $resourceType, (array) $resourceType);
        $actual = [];

        /** @var array $resource */
        foreach ($collection as $resource) {

            if (!isset($resource[Keys::KEYWORD_TYPE])) {
                PHPUnit::fail('Encountered a resource without a type key.');
            }

            $type = $resource[Keys::KEYWORD_TYPE];

            if (!isset($actual[$type])) {
                $actual[$type] = $type;
            }
        }

        PHPUnit::assertEquals($expected, $actual, 'Unexpected resource types in data collection.');

        return $this;
    }

    /**
     * @param $resourceType
     * @param bool $allowEmpty
     * @return TestResponse
     * @deprecated use `assertDataCollection`
     */
    public function seeDataCollection($resourceType, $allowEmpty = true)
    {
        return $this->assertDataCollection($resourceType, $allowEmpty);
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

        $attributes = isset($expected[Keys::KEYWORD_ATTRIBUTES]) ?
            $expected[Keys::KEYWORD_ATTRIBUTES] : [];

        $relationships = isset($expected[Keys::KEYWORD_RELATIONSHIPS]) ?
            $this->normalizeResourceRelationships($expected[Keys::KEYWORD_RELATIONSHIPS]) : [];

        /** Check the structure is as expected. */
        $structure = [
            Keys::KEYWORD_TYPE,
            Keys::KEYWORD_ID,
        ];

        if (!empty($attributes)) {
            $structure[Keys::KEYWORD_ATTRIBUTES] = array_keys($attributes);
        }

        if (!empty($relationships)) {
            $structure[Keys::KEYWORD_RELATIONSHIPS] = array_keys($relationships);
        }

        $this->assertJsonStructure([
            Keys::KEYWORD_DATA => $structure,
        ]);

        $data = $this->decodeResponseJson()[Keys::KEYWORD_DATA];

        /** Have we got the correct resource type? */
        PHPUnit::assertEquals($expected[Keys::KEYWORD_TYPE], $data[Keys::KEYWORD_TYPE], 'Unexpected resource type');

        /** Have we got the correct resource id? */
        if (isset($expected[Keys::KEYWORD_ID])) {
            PHPUnit::assertEquals($expected[Keys::KEYWORD_ID], $data[Keys::KEYWORD_ID], 'Unexpected resource id');
        }

        /** Have we got the correct attributes? */
        PHPUnit::assertArraySubset(
            $attributes,
            $data[Keys::KEYWORD_ATTRIBUTES],
            false,
            "Unexpected resource attributes\n" . json_encode($data[Keys::KEYWORD_ATTRIBUTES])
        );

        /** Have we got the correct relationships? */
        $actualRelationships = isset($data[Keys::KEYWORD_RELATIONSHIPS]) ? $data[Keys::KEYWORD_RELATIONSHIPS] : [];
        PHPUnit::assertArraySubset(
            $relationships,
            $actualRelationships,
            false,
            "Unexpected resource relationships\n" . json_encode($actualRelationships)
        );

        return $this;
    }

    /**
     * @param array $expected
     * @return TestResponse
     * @deprecated use `assertDataResource`
     */
    public function seeDataResource(array $expected)
    {
        return $this->assertDataResource($expected);
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
        if (is_null($resourceType)) {
            $this->expectedResourceType();
        }

        $this->assertJsonStructure([
            Keys::KEYWORD_DATA,
        ]);

        $data = (array) $this->decodeResponseJson()[Keys::KEYWORD_DATA];

        if (is_null($id)) {
            PHPUnit::assertNull($data, 'Expecting data to be null (no identifier present).');
            return $this;
        }

        $actualType = isset($data[Keys::KEYWORD_TYPE]) ? $data[Keys::KEYWORD_TYPE] : null;
        $actualId = isset($data[Keys::KEYWORD_ID]) ? $data[Keys::KEYWORD_ID] : null;

        PHPUnit::assertContains($actualType, (array) $resourceType, 'Unexpected resource type in identifier.');
        PHPUnit::assertEquals($id, $actualId, 'Unexpected resource id.');

        return $this;
    }

    /**
     * @param string|null $resourceType
     * @param string|null $id
     * @return $this
     * @deprecated use `assertDataResourceIdentifier`
     */
    public function seeDataResourceIdentifier($resourceType = null, $id = null)
    {
        return $this->assertDataResourceIdentifier($resourceType, $id);
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
     * @return DocumentTester
     * @deprecated use `assertDocument`
     */
    public function seeDocument()
    {
        return $this->assertDocument();
    }

    /**
     * @return ErrorsTester
     */
    public function assertErrors()
    {
        return $this->assertDocument()->assertErrors();
    }

    /**
     * @return ErrorsTester
     * @deprecated use `assertErrors`
     */
    public function seeErrors()
    {
        return $this->assertErrors();
    }

    /**
     * @return string
     */
    protected function expectedResourceType()
    {
        if (!$this->expectedResourceType) {
            PHPUnit::fail('You must have provided the expected resource type to the test resposne helper.');
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

    /**
     * @param array $relationships
     * @return array
     */
    private function normalizeResourceRelationships(array $relationships)
    {
        $normalized = [];

        foreach ($relationships as $key => $value) {

            if (is_numeric($key)) {
                $key = $value;
                $value = [];
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
