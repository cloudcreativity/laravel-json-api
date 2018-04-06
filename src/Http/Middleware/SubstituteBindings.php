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

namespace CloudCreativity\LaravelJsonApi\Http\Middleware;

use Closure;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Illuminate\Http\Request;

/**
 * Class SubstituteBindings
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class SubstituteBindings
{

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $jsonApiRequest = json_api_request();
        $store = json_api()->getStore();
        $record = null;

        /** If the request is a read record request, we need to do this so eager loading occurs. */
        if ($jsonApiRequest->isReadResource()) {
            $record = $store->readRecord(
                $jsonApiRequest->getResourceType(),
                $jsonApiRequest->getResourceId(),
                $jsonApiRequest->getParameters()
            );
        } elseif ($jsonApiRequest->getResourceId()) {
            $record = $store->findOrFail(ResourceIdentifier::create(
                $jsonApiRequest->getResourceType(),
                $jsonApiRequest->getResourceId()
            ));
        }

        if ($record) {
            $request->route()->setParameter(ResourceRegistrar::PARAM_RESOURCE_ID, $record);
        }

        return $next($request);
    }
}
