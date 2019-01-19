<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Routing;

use Illuminate\Contracts\Routing\Registrar;

/**
 * Class ApiGroup
 *
 * @package CloudCreativity\LaravelJsonApi
 */
final class ApiGroup
{

    /**
     * @var Registrar
     */
    private $router;

    /**
     * @var array
     */
    private $options;

    /**
     * ApiGroup constructor.
     *
     * @param Registrar $router
     * @param array $options
     */
    public function __construct(Registrar $router, array $options = [])
    {
        $this->router = $router;
        $this->options = $options;
    }

    /**
     * Register routes for the supplied resource type
     *
     * @param string $resourceType
     * @param array $options
     * @return ResourceRegistration
     */
    public function resource(string $resourceType, array $options = []): ResourceRegistration
    {
        return new ResourceRegistration(
            $this->router,
            $resourceType,
            array_merge($this->options, $options)
        );
    }

}
