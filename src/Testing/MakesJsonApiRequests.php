<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Document\GeneratesLinks;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface as Keys;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use PHPUnit_Framework_Assert as PHPUnit;

/**
 * Class MakesJsonApiRequests
 * @package CloudCreativity\LaravelJsonApi\Testing
 */
trait MakesJsonApiRequests
{

    use MakesHttpRequests,
        GeneratesLinks;

    /**
     * Visit the given URI with a JSON API request.
     *
     * @param $method
     * @param LinkInterface|string $uri
     * @param array $data
     * @param array $headers
     * @return $this
     */
    protected function jsonApi($method, $uri, array $data = [], array $headers = [])
    {
        if ($uri instanceof LinkInterface) {
            $uri = $uri->getSubHref();
        }

        $headers = array_merge([
            'CONTENT_TYPE' => MediaTypeInterface::JSON_API_MEDIA_TYPE,
            'Accept' => MediaTypeInterface::JSON_API_MEDIA_TYPE,
        ], $headers);

        return $this->json($method, $uri, $data, $headers);
    }

    /**
     * Assert response is a JSON API response.
     *
     * @param int $statusCode
     * @param string $contentType
     * @return $this
     */
    protected function assertJsonApiResponse(
        $statusCode = Response::HTTP_OK,
        $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ) {
        $this->seeStatusCode($statusCode)
            ->seeHeader('Content-Type', $contentType);

        return $this;
    }

    /**
     * Assert response is a JSON API resource index response.
     *
     * @param $resourceType
     * @param string $contentType
     * @return $this
     */
    protected function assertIndexResponse(
        $resourceType,
        $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ) {
        $this->assertJsonApiResponse(Response::HTTP_OK, $contentType)
            ->seeDataCollection($resourceType);

        return $this;
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
    protected function assertCreateResponse(
        array $expected,
        $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ) {
        $this->assertJsonApiResponse(Response::HTTP_CREATED, $contentType)
            ->seeDataResource($expected);

        $data = $this->decodeResponseJson()[Keys::KEYWORD_DATA];
        $id = $data[Keys::KEYWORD_ID];
        $this->seeHeader('Location');

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
    protected function assertReadResponse(
        array $expected,
        $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ) {
        $this->assertJsonApiResponse(Response::HTTP_OK, $contentType)
            ->seeDataResource($expected);

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
    protected function assertUpdateResponse(
        array $expected,
        $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ) {
        $this->assertJsonApiResponse(Response::HTTP_OK, $contentType)
            ->seeDataResource($expected);

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
    protected function assertDeleteResponse(
        $statusCode = Response::HTTP_NO_CONTENT,
        $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ) {
        if (Response::HTTP_NO_CONTENT == $statusCode) {
            $this->seeStatusCode(Response::HTTP_NO_CONTENT);
        } else {
            $this->assertJsonApiResponse($statusCode, $contentType);
        }

        return $this;
    }

    /**
     * @param $expected
     * @return $this
     */
    protected function seeStatusCode($expected)
    {
        $actual = $this->response->getStatusCode();
        $message = "Expected status code {$expected}, got {$actual}";
        $content = (array) json_decode((string) $this->response->getContent(), true);

        if (isset($content[Keys::KEYWORD_ERRORS])) {
            $message .= " with errors:\n" . json_encode($content, JSON_PRETTY_PRINT);
        }

        PHPUnit::assertEquals($expected, $actual, $message);

        return $this;
    }

    /**
     * See that there is a collection of resources as primary data.
     *
     * @param $resourceType
     * @param bool $allowEmpty
     */
    protected function seeDataCollection($resourceType, $allowEmpty = true)
    {
        $this->seeJsonStructure([
            Keys::KEYWORD_DATA => [
                '*' => [Keys::KEYWORD_TYPE],
            ],
        ]);

        $collection = $this->decodeResponseJson()[Keys::KEYWORD_DATA];

        if (!$allowEmpty) {
            PHPUnit::assertNotEmpty($collection, 'Data collection is empty');
        }

        /** @var array $resource */
        foreach ($collection as $resource) {
            $type = $resource[Keys::KEYWORD_TYPE];
            if ($resourceType !== $type) {
                PHPUnit::fail('Unexpected resource type in collection: ' . $type);
            }
        }
    }

    /**
     * See that there is a resource object as primary data.
     *
     * @param array $expected
     *      the expected array representation of the resource.
     * @return $this
     */
    protected function seeDataResource(array $expected)
    {
        if (!isset($expected[Keys::KEYWORD_TYPE])) {
            PHPUnit::fail(sprintf('Expected resource must have a "%s" key.', Keys::KEYWORD_TYPE));
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

        $this->seeJsonStructure([
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
        PHPUnit::assertArraySubset($attributes, $data[Keys::KEYWORD_ATTRIBUTES], false, 'Unexpected resource attributes');
        /** Have we got the correct relationships? */
        $actualRelationships = isset($data[Keys::KEYWORD_RELATIONSHIPS]) ? $data[Keys::KEYWORD_RELATIONSHIPS] : [];
        PHPUnit::assertArraySubset($relationships, $actualRelationships, false, 'Unexpected resource relationships');
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
