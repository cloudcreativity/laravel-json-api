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
        if ($record = json_api_request()->getRecord()) {
            $request->route()->setParameter(ResourceRegistrar::PARAM_RESOURCE_ID, $record);
        }

        return $next($request);
    }
}
