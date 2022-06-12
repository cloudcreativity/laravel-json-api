<?php

/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\View;

use CloudCreativity\LaravelJsonApi\Encoder\Encoder;
use CloudCreativity\LaravelJsonApi\Http\Query\QueryParameters;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;

/**
 * Class Renderer
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Renderer
{

    /**
     * @var JsonApiService
     */
    private $service;

    /**
     * @var Encoder|null
     */
    private $encoder;

    /**
     * @param $expression
     * @return string
     */
    public static function compileWith($expression)
    {
        return "<?php app('json-api.renderer')->with($expression); ?>";
    }

    /**
     * @param $expression
     * @return string
     */
    public static function compileEncode($expression)
    {
        return "<?php echo app('json-api.renderer')->encode($expression); ?>";
    }

    /**
     * Directive constructor.
     *
     * @param JsonApiService $service
     */
    public function __construct(JsonApiService $service)
    {
        $this->service = $service;
    }

    /**
     * @param $apiName
     * @param int $options
     * @param int $depth
     */
    public function with($apiName = null, $options = 0, $depth = 512)
    {
        $this->encoder = $this->service->api($apiName)->encoder($options, $depth);
    }

    /**
     * @param $data
     * @param string|array|null $includePaths
     * @param array|null $fieldSets
     * @return string
     */
    public function encode($data, $includePaths = null, $fieldSets = null)
    {
        if (!$this->encoder) {
            $this->with();
        }

        $params = null;

        if ($includePaths || $fieldSets) {
            $params = new QueryParameters(
                $includePaths ? (array) $includePaths : $includePaths,
                $fieldSets
            );
        }

        return $this->encoder
            ->withEncodingParameters($params)
            ->encodeData($data);
    }
}
