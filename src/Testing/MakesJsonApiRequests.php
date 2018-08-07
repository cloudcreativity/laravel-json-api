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

use CloudCreativity\LaravelJsonApi\Api\Api;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface as Keys;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface as Params;
use RuntimeException;

/**
 * Class MakesJsonApiRequests
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait MakesJsonApiRequests
{

    /**
     * Visit the given URI with a JSON API request.
     *
     * @param $method
     * @param LinkInterface|string $uri
     * @param array|string $data
     * @param array $headers
     * @return TestResponse
     */
    protected function jsonApi($method, $uri, $data = [], array $headers = [])
    {
        if ($uri instanceof LinkInterface) {
            $uri = $uri->getSubHref();
        }

        if (is_array($data) && !empty($data)) {
            $content = json_encode($data);
        } else {
            $content = $data ?: null;
        }

        $headers = $this->normalizeHeaders($headers, $content);

        /** @var TestResponse $response */
        $response = $this->call(
            $method, $uri, [], [], [], $this->transformHeadersToServerVars($headers), $content
        );

        return $response;
    }

    /**
     * @param $uri
     * @param array|string $data
     * @param array $headers
     * @return TestResponse
     */
    protected function getJsonApi($uri, $data = [], array $headers = [])
    {
        return $this->jsonApi('GET', $uri, $data, $headers);
    }

    /**
     * @param $uri
     * @param array|string $data
     * @param array $headers
     * @return TestResponse
     */
    protected function postJsonApi($uri, $data = [], array $headers = [])
    {
        return $this->jsonApi('POST', $uri, $data, $headers);
    }

    /**
     * @param $uri
     * @param array|string $data
     * @param array $headers
     * @return TestResponse
     */
    protected function patchJsonApi($uri, $data = [], array $headers = [])
    {
        return $this->jsonApi('PATCH', $uri, $data, $headers);
    }

    /**
     * @param $uri
     * @param array|string $data
     * @param array $headers
     * @return TestResponse
     */
    protected function deleteJsonApi($uri, $data = [], array $headers = [])
    {
        return $this->jsonApi('DELETE', $uri, $data, $headers);
    }

    /**
     * @param array $headers
     * @param string|null $content
     * @return array
     */
    protected function normalizeHeaders(array $headers, $content = null)
    {
        $defaultHeaders = ['Accept' => $this->acceptMediaType()];

        if (!is_null($content)) {
            $defaultHeaders['CONTENT_LENGTH'] = mb_strlen($content, '8bit');
            $defaultHeaders['CONTENT_TYPE'] = $this->contentMediaType();
        }

        return array_merge($defaultHeaders, $headers);
    }

    /**
     * @param $response
     * @return TestResponse
     */
    protected function createTestResponse($response)
    {
        $resourceType = property_exists($this, 'resourceType') ? $this->resourceType : null;

        return new TestResponse($response, $resourceType, $this->expectedMediaType());
    }

    /**
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    protected function doSearch(array $params = [], array $headers = [])
    {
        $params = $this->addDefaultRouteParams($params);
        $uri = $this->api()->url()->index($this->resourceType(), $params);

        return $this->getJsonApi($uri, [], $headers);
    }

    /**
     * @param array|Collection|UrlRoutable $ids
     *      the ids - may contain UrlRoutable objects (includes Models)
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    protected function doSearchById($ids, array $params = [], array $headers = [])
    {
        if (!isset($params[Params::PARAM_FILTER])) {
            $params[Params::PARAM_FILTER] = [];
        }

        $params[Params::PARAM_FILTER][Keys::KEYWORD_ID] = $this->normalizeIds($ids);

        return $this->doSearch($params, $headers);
    }

    /**
     * Assert that the resource's search (index) route has not been registered.
     *
     * @return void
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
     * @param array $data
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    protected function doCreate(array $data, array $params = [], array $headers = [])
    {
        $params = $this->addDefaultRouteParams($params);
        $uri = $this->api()->url()->create($this->resourceType(), $params);

        return $this->postJsonApi($uri, ['data' => $data], $headers);
    }

    /**
     * Assert that the resource's create route has not been registered.
     *
     * @return void
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
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    protected function doRead($resourceId, array $params = [], array $headers = [])
    {
        $params = $this->addDefaultRouteParams($params);
        $uri = $this->api()->url()->read($this->resourceType(), $resourceId, $params);

        return $this->getJsonApi($uri, [], $headers);
    }

    /**
     * Assert that the resource's read route has not been registered.
     *
     * @return void
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
     * @param array $data
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    protected function doUpdate(array $data, array $params = [], array $headers = [])
    {
        $id = isset($data[Keys::KEYWORD_ID]) ? $data[Keys::KEYWORD_ID] : null;

        if (empty($id)) {
            throw new InvalidArgumentException('Expecting provided data to contain a resource id.');
        }

        $params = $this->addDefaultRouteParams($params);
        $uri = $this->api()->url()->update($this->resourceType(), $id, $params);

        return $this->patchJsonApi($uri, ['data' => $data], $headers);
    }

    /**
     * Assert that the resource's update route has not been registered.
     *
     * @return void
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
     * @param $resourceId
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    protected function doDelete($resourceId, array $params = [], array $headers = [])
    {
        $params = $this->addDefaultRouteParams($params);
        $uri = $this->api()->url()->delete($this->resourceType(), $resourceId, $params);

        return $this->deleteJsonApi($uri, [], $headers);
    }

    /**
     * Assert that the resource's delete route has not been registered.
     *
     * @return void
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
     * @param $resourceId
     * @param $relationshipName
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    protected function doReadRelated($resourceId, $relationshipName, array $params = [], array $headers = [])
    {
        $params = $this->addDefaultRouteParams($params);
        $uri = $this->api()->url()->relatedResource($this->resourceType(), $resourceId, $relationshipName, $params);

        return $this->getJsonApi($uri, [], $headers);
    }

    /**
     * Assert that the related resource route has not been registered for the supplied relationship name.
     *
     * @param $relationshipName
     * @return void
     */
    protected function assertCannotReadRelated($relationshipName)
    {
        $readable = true;

        try {
            $this->api()->url()->relatedResource(
                $this->resourceType(),
                '1',
                $relationshipName,
                $this->addDefaultRouteParams([])
            );
        } catch (InvalidArgumentException $ex) {
            $readable = false;
        }

        $this->assertFalse($readable, "Related resource $relationshipName route exists.");
    }

    /**
     * @param $resourceId
     * @param $relationshipName
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    protected function doReadRelationship($resourceId, $relationshipName, array $params = [], array $headers = [])
    {
        $params = $this->addDefaultRouteParams($params);
        $uri = $this->api()->url()->readRelationship($this->resourceType(), $resourceId, $relationshipName, $params);

        return $this->getJsonApi($uri, [], $headers);
    }

    /**
     * Assert that the read relationship route has not been registered for the supplied relationship name.
     *
     * @param $relationshipName
     * @return void
     */
    protected function assertCannotReadRelationship($relationshipName)
    {
        $readable = true;

        try {
            $this->api()->url()->readRelationship(
                $this->resourceType(),
                '1',
                $relationshipName,
                $this->addDefaultRouteParams([])
            );
        } catch (InvalidArgumentException $ex) {
            $readable = false;
        }

        $this->assertFalse($readable, "Read relationship $relationshipName route exists.");
    }

    /**
     * @param mixed $resourceId
     * @param string $relationshipName
     * @param array|null $data
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    protected function doReplaceRelationship(
        $resourceId,
        $relationshipName,
        $data,
        array $params = [],
        array $headers = []
    ) {
        $params = $this->addDefaultRouteParams($params);
        $uri = $this->api()->url()->replaceRelationship(
            $this->resourceType(),
            $resourceId,
            $relationshipName,
            $params
        );

        return $this->patchJsonApi($uri, ['data' => $data], $headers);
    }

    /**
     * Assert that the replace relationship route has not been registered for the supplied relationship name.
     *
     * @param $relationshipName
     * @return void
     */
    protected function assertCannotReplaceRelationship($relationshipName)
    {
        $replaceable = true;

        try {
            $this->api()->url()->replaceRelationship(
                $this->resourceType(),
                '1',
                $relationshipName,
                $this->addDefaultRouteParams([])
            );
        } catch (InvalidArgumentException $ex) {
            $replaceable = false;
        }

        $this->assertFalse($replaceable, "Replace relationship $relationshipName route exists.");
    }

    /**
     * @param mixed $resourceId
     * @param string $relationshipName
     * @param array $data
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    protected function doAddToRelationship(
        $resourceId,
        $relationshipName,
        array $data,
        array $params = [],
        array $headers = []
    ) {
        $params = $this->addDefaultRouteParams($params);
        $uri = $this->api()->url()->addRelationship(
            $this->resourceType(),
            $resourceId,
            $relationshipName,
            $params
        );

        return $this->postJsonApi($uri, ['data' => $data], $headers);
    }

    /**
     * Assert that the add-to relationship route has not been registered for the supplied relationship name.
     *
     * @param $relationshipName
     * @return void
     */
    protected function assertCannotAddToRelationship($relationshipName)
    {
        $replaceable = true;

        try {
            $this->api()->url()->addRelationship(
                $this->resourceType(),
                '1',
                $relationshipName,
                $this->addDefaultRouteParams([])
            );
        } catch (InvalidArgumentException $ex) {
            $replaceable = false;
        }

        $this->assertFalse($replaceable, "Add to relationship $relationshipName route exists.");
    }

    /**
     * @param mixed $resourceId
     * @param string $relationshipName
     * @param array|null $data
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    protected function doRemoveFromRelationship(
        $resourceId,
        $relationshipName,
        $data,
        array $params = [],
        array $headers = []
    ) {
        $params = $this->addDefaultRouteParams($params);
        $uri = $this->api()->url()->removeRelationship(
            $this->resourceType(),
            $resourceId,
            $relationshipName,
            $params
        );

        return $this->deleteJsonApi($uri, ['data' => $data], $headers);
    }

    /**
     * Assert that the remove-from relationship route has not been registered for the supplied relationship name.
     *
     * @param $relationshipName
     * @return void
     */
    protected function assertCannotRemoveFromRelationship($relationshipName)
    {
        $replaceable = true;

        try {
            $this->api()->url()->removeRelationship(
                $this->resourceType(),
                '1',
                $relationshipName,
                $this->addDefaultRouteParams([])
            );
        } catch (InvalidArgumentException $ex) {
            $replaceable = false;
        }

        $this->assertFalse($replaceable, "Remove from relationship $relationshipName route exists.");
    }

    /**
     * @return mixed
     */
    protected function resourceType()
    {
        $resourceType = property_exists($this, 'resourceType') ? $this->resourceType : null;

        if (!$resourceType) {
            throw new RuntimeException('You must set a resource type property on your test case.');
        }

        return $resourceType;
    }

    /**
     * Get the media type to use for the Accept header.
     *
     * @return string
     */
    protected function acceptMediaType()
    {
        $mediaType = property_exists($this, 'acceptMediaType') ? $this->acceptMediaType : null;

        return $mediaType ?: MediaTypeInterface::JSON_API_MEDIA_TYPE;
    }

    /**
     * Get the media type to use for the Content-Type header.
     *
     * @return string
     */
    protected function contentMediaType()
    {
        $mediaType = property_exists($this, 'contentMediaType') ? $this->contentMediaType : null;

        return $mediaType ?: $this->acceptMediaType();
    }

    /**
     * Get the expected media type for a response that contains body.
     *
     * @return string
     */
    protected function expectedMediaType()
    {
        $mediaType = property_exists($this, 'responseMediaType') ? $this->responseMediaType : null;

        return $mediaType ?: $this->acceptMediaType();
    }

    /**
     * @return Api
     */
    protected function api()
    {
        $api = property_exists($this, 'api') ? $this->api : null;

        return json_api($api);
    }

    /**
     * Add default parameters to those provided to a `do*` method.
     *
     * Classes can override this method if they need to add any default parameters for constructing
     * the route link.
     *
     * @param array $params
     * @return array
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
     */
    protected function normalizeId($id)
    {
        return ($id instanceof UrlRoutable) ? $id->getRouteKey() : $id;
    }

}
