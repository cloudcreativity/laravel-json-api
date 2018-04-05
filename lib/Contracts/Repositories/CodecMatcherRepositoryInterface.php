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

namespace CloudCreativity\JsonApi\Contracts\Repositories;

use CloudCreativity\JsonApi\Contracts\Utils\ConfigurableInterface;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;

/**
 * Interface CodecMatcherRepositoryInterface
 *
 * @package CloudCreativity\JsonApi
 * @deprecated
 */
interface CodecMatcherRepositoryInterface extends ConfigurableInterface
{


    /** Config key for a codec matcher's encoders */
    const ENCODERS = 'encoders';
    /** Config key for a codec matcher's decoders */
    const DECODERS = 'decoders';

    /** Config key for encoder options. */
    const OPTIONS = 'options';
    /** Config key for encoder depth. */
    const DEPTH = 'depth';

    /**
     * Register the schemas to use when creating a codec matcher.
     *
     * @param ContainerInterface $schemas
     * @return $this
     */
    public function registerSchemas(ContainerInterface $schemas);

    /**
     * Register the URL prefix to use when creating a codec matcher.
     *
     * @param $urlPrefix
     * @return $this
     */
    public function registerUrlPrefix($urlPrefix);

    /**
     * @return CodecMatcherInterface
     */
    public function getCodecMatcher();

}
