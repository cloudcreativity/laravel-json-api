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

namespace CloudCreativity\LaravelJsonApi\Http\Middleware;

use Closure;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\JsonApi\Http\Middleware\ValidatesRequests;
use Illuminate\Http\Request;

/**
 * Class ValidateRequest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ValidateRequest
{

    use ValidatesRequests;

    /**
     * @param Request $request
     * @param Closure $next
     * @param string|null $inverse
     *      the inverse resource type for relationship endpoints.
     * @return mixed
     */
    public function handle($request, Closure $next, $inverse = null)
    {
        $api = json_api();
        $inboundRequest = json_api_request();

        if ($inboundRequest->getRelationshipName() && !$inverse) {
            throw new RuntimeException(sprintf(
                'Expecting an inverse resource type for the %s relationship on the %s resource.',
                $inboundRequest->getRelationshipName(),
                $inboundRequest->getResourceType()
            ));
        }

        $resourceValidators = $api->getContainer()->getValidatorsByResourceType(
            $inboundRequest->getResourceType()
        );

        if ($resourceValidators) {
            $this->validate(
                $inboundRequest,
                $api->getStore(),
                $resourceValidators,
                $inverse ? $api->getContainer()->getValidatorsByResourceType($inverse) : null
            );
        }

        return $next($request);
    }

}
