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

use CloudCreativity\LaravelJsonApi\Document\GeneratesLinks;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface as Keys;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface as Params;
use PHPUnit_Framework_Assert as PHPUnit;

/**
 * Class MakesJsonApiRequests
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait MakesJsonApiRequests
{

    use GeneratesLinks, MakesHttpRequests;

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
        $route = $this->resolveRouteName();
        $uri = $this->linkTo()->index($route, $params);

        return $this->jsonApi('GET', $uri, [], $headers);
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
     * @param array $data
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    protected function doCreate(array $data, array $params = [], array $headers = [])
    {
        $params = $this->addDefaultRouteParams($params);
        $route = $this->resolveRouteName();
        $uri = $this->linkTo()->create($route, $params);

        return $this->jsonApi('POST', $uri, ['data' => $data], $headers);
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
        $route = $this->resolveRouteName();
        $uri = $this->linkTo()->read($route, $resourceId, $params);

        return $this->jsonApi('GET', $uri, $headers);
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
            PHPUnit::fail('Expecting data to contain a resource id.');
        }

        $params = $this->addDefaultRouteParams($params);
        $route = $this->resolveRouteName();
        $uri = $this->linkTo()->read($route, $id, $params);

        return $this->jsonApi('PATCH', $uri, ['data' => $data], $headers);
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
        $route = $this->resolveRouteName();
        $uri = $this->linkTo()->read($route, $resourceId, $params);

        return $this->jsonApi('DELETE', $uri, [], $headers);
    }

    /**
     * @return mixed
     */
    protected function resourceType()
    {
        $resourceType = property_exists($this, 'resourceType') ? $this->resourceType : null;

        if (!$resourceType) {
            PHPUnit::fail('You must set a resource type property on your test case.');
        }

        return $resourceType;
    }

    /**
     * @return string
     */
    protected function resourceRouteName()
    {
        $prefix = property_exists($this, 'routePrefix') ? $this->routePrefix : '';

        return $prefix . $this->resourceType();
    }

    /**
     * @return string
     * @deprecated use `resourceRouteName`
     */
    protected function resolveRouteName()
    {
        return $this->resourceRouteName();
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
