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

use CloudCreativity\JsonApi\Testing\ResourcesTester;
use CloudCreativity\JsonApi\Testing\ResourceTester;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface as Keys;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface as Params;

/**
 * Class InteractsWithResources
 *
 * @package CloudCreativity\LaravelJsonApi
 *
 * This trait MUST be used on a class that uses this trait:
 * Illuminate\Foundation\Testing\Concerns\MakesHttpRequests
 */
trait InteractsWithResources
{

    use MakesJsonApiRequests;

    /**
     * Get the resource type that this test case is testing.
     *
     * @return string
     */
    abstract protected function getResourceType();

    /**
     * Get the route prefix that should be added to the resource type to create the route name.
     *
     * Test case classes should overload this method if they are registering resource types
     * under a group with a route name. E.g. if your resource `posts` is registered under a route
     * group name alias of `api::` then this method needs to return `api::` as the route name
     * for the `posts` resource will be `api::posts`.
     *
     * @return string|null
     */
    protected function getRoutePrefix()
    {
        return null;
    }

    /**
     * @param array $params
     * @param array $headers
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    protected function doCreate(array $data, array $params = [], array $headers = [])
    {
        $params = $this->addDefaultRouteParams($params);
        $route = $this->resolveRouteName();
        $uri = $this->linkTo()->index($route, $params);

        return $this->jsonApi('POST', $uri, ['data' => $data], $headers);
    }

    /**
     * @param mixed $resourceId
     * @param array $params
     * @param array $headers
     * @return $this
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
     * @return $this
     */
    protected function doUpdate(array $data, array $params = [], array $headers = [])
    {
        $id = isset($data[Keys::KEYWORD_ID]) ? $data[Keys::KEYWORD_ID] : null;

        if (empty($id)) {
            throw new InvalidArgumentException('Expecting data to contain a resource id');
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
     * @return $this
     */
    protected function doDelete($resourceId, array $params = [], array $headers = [])
    {
        $params = $this->addDefaultRouteParams($params);
        $route = $this->resolveRouteName();
        $uri = $this->linkTo()->read($route, $resourceId, $params);

        return $this->jsonApi('DELETE', $uri, [], $headers);
    }

    /**
     * Assert that a search response is a collection only containing the expected resource type.
     *
     * @param string $contentType
     * @return ResourcesTester
     */
    protected function assertSearchResponse($contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE)
    {
        $this->assertJsonApiResponse(Response::HTTP_OK, $contentType);

        return $this
            ->seeDocument()
            ->assertResourceCollection()
            ->assertTypes($this->getResourceType());
    }

    /**
     * Assert that a search response contains a singleton resource with the expected id.
     *
     * @param string|int|UrlRoutable $expectedId
     * @param string $contentType
     * @return ResourceTester
     * @todo needs to support `null` responses
     */
    protected function assertSearchOneResponse($expectedId, $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE)
    {
        if ($expectedId instanceof UrlRoutable) {
            $expectedId = $expectedId->getRouteKey();
        }

        $this->assertJsonApiResponse(Response::HTTP_OK, $contentType);

        return $this
            ->seeDocument()
            ->assertResource()
            ->assertIs($this->getResourceType(), $expectedId);
    }

    /**
     * Assert that the response to a search by id(s) request contains the expected ids.
     *
     * @param array|Collection|UrlRoutable $expectedIds
     *      the ids - may contain UrlRoutable objects (e.g. Models)
     * @param string $contentType
     * @return ResourcesTester
     */
    protected function assertSearchByIdResponse($expectedIds, $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE)
    {
        $this->assertJsonApiResponse(Response::HTTP_OK, $contentType);

        return $this
            ->seeDocument()
            ->assertResourceCollection()
            ->assertContainsOnly([
                $this->getResourceType() => $this->normalizeIds($expectedIds),
            ]);
    }

    /**
     * @return string
     */
    protected function resolveRouteName()
    {
        return $this->getRoutePrefix() . $this->getResourceType();
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
     * @param array|Collection|UrlRoutable $ids
     * @return array
     */
    protected function normalizeIds($ids)
    {
        if ($ids instanceof UrlRoutable) {
            $ids = [$ids];
        }

        $ids = new Collection($ids);

        return $ids->map(function ($id) {
            return ($id instanceof UrlRoutable) ? $id->getRouteKey() : $id;
        })->all();
    }
}
