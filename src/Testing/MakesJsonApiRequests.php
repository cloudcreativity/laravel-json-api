<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
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

    use MakesHttpRequests;

    /**
     * Visit the given URI with a JSON API request.
     *
     * @param $method
     * @param LinkInterface|string $uri
     * @param array $data
     * @param array $headers
     * @return TestResponse
     */
    protected function jsonApi($method, $uri, array $data = [], array $headers = [])
    {
        if ($uri instanceof LinkInterface) {
            $uri = $uri->getSubHref();
        }

        $content = $data ? json_encode($data) : null;
        $headers = $this->normalizeHeaders($headers, $content);

        /** @var TestResponse $response */
        $response = $this->call(
            $method, $uri, [], [], [], $this->transformHeadersToServerVars($headers), $content
        );

        return $response;
    }

    /**
     * @param $uri
     * @param array $data
     * @param array $headers
     * @return TestResponse
     */
    protected function getJsonApi($uri, array $data = [], array $headers = [])
    {
        return $this->jsonApi('GET', $uri, $data, $headers);
    }

    /**
     * @param $uri
     * @param array $data
     * @param array $headers
     * @return TestResponse
     */
    protected function postJsonApi($uri, array $data = [], array $headers = [])
    {
        return $this->jsonApi('POST', $uri, $data, $headers);
    }

    /**
     * @param $uri
     * @param array $data
     * @param array $headers
     * @return TestResponse
     */
    protected function patchJsonApi($uri, array $data = [], array $headers = [])
    {
        return $this->jsonApi('PATCH', $uri, $data, $headers);
    }

    /**
     * @param $uri
     * @param array $data
     * @param array $headers
     * @return TestResponse
     */
    protected function deleteJsonApi($uri, array $data = [], array $headers = [])
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
        $defaultHeaders = ['Accept' => MediaTypeInterface::JSON_API_MEDIA_TYPE];

        if (!is_null($content)) {
            $defaultHeaders['CONTENT_LENGTH'] = mb_strlen($content, '8bit');
            $defaultHeaders['CONTENT_TYPE'] = MediaTypeInterface::JSON_API_MEDIA_TYPE;
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

        return new TestResponse($response, $resourceType);
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
     * @return Api
     */
    protected function api()
    {
        $api = property_exists($this, 'api') ? $this->api : 'default';

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
            return ($id instanceof UrlRoutable) ? $id->getRouteKey() : $id;
        })->all();
    }

}
