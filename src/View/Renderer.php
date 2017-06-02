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

namespace CloudCreativity\LaravelJsonApi\View;

use CloudCreativity\JsonApi\Encoder\Encoder;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;

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
     * @var Request
     */
    private $request;

    /**
     * @var Encoder|null
     */
    private $encoder;

    /**
     * @param $expression
     * @return string
     */
    public static function compileEncoder($expression)
    {
        $class = self::class;

        return "<?php app('$class')->encoder($expression); ?>";
    }

    /**
     * @param $expression
     * @return string
     */
    public static function compileData($expression)
    {
        $class = self::class;

        return "<?php echo app('$class')->encodeData($expression); ?>";
    }

    /**
     * Directive constructor.
     *
     * @param JsonApiService $service
     * @param Request $request
     */
    public function __construct(JsonApiService $service, Request $request)
    {
        $this->service = $service;
        $this->request = $request;
    }

    /**
     * @param $apiName
     * @param string|null $host
     * @param int $options
     * @param int $depth
     */
    public function encoder($apiName, $host = null, $options = 0, $depth = 512)
    {
        $host = $host ?: $this->request->getSchemeAndHttpHost();

        $this->encoder = $this->service->encoder($apiName, $host, $options, $depth);
    }

    /**
     * @param $data
     * @param string|array|null $includePaths
     * @param array|null $fieldSets
     * @return string
     */
    public function encodeData($data, $includePaths = null, $fieldSets = null)
    {
        if (!$this->encoder) {
            $this->encoder('default');
        }

        $params = new EncodingParameters(
            $includePaths ? (array) $includePaths : $includePaths,
            $fieldSets
        );

        return $this->encoder->encodeData($data, $params);
    }
}
