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

declare(strict_types=1);

namespace CloudCreativity\LaravelJsonApi\Contracts\Http\Headers;

use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;

interface HeaderInterface
{
    /** Header name that contains format of output data from client */
    const HEADER_ACCEPT = 'Accept';

    /** Header name that contains format of input data from client */
    const HEADER_CONTENT_TYPE = 'Content-Type';

    /** Header name that location of newly created resource */
    const HEADER_LOCATION = 'Location';

    /**
     * Get header name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get media types.
     *
     * @return MediaTypeInterface[]
     */
    public function getMediaTypes(): array;
}