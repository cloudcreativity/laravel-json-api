<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Http\Headers;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\AcceptHeaderInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderParametersInterface;

class HeaderParameters implements HeaderParametersInterface
{
    /**
     * @var AcceptHeaderInterface
     */
    private AcceptHeaderInterface $accept;

    /**
     * @var HeaderInterface|null
     */
    private ?HeaderInterface $contentType;

    /**
     * HeaderParameters constructor.
     *
     * @param AcceptHeaderInterface $accept
     * @param HeaderInterface|null $contentType
     */
    public function __construct(AcceptHeaderInterface $accept, HeaderInterface $contentType = null)
    {
        $this->accept = $accept;
        $this->contentType = $contentType;
    }

    /**
     * @inheritdoc
     */
    public function getAcceptHeader(): AcceptHeaderInterface
    {
        return $this->accept;
    }

    /**
     * @inheritdoc
     */
    public function getContentTypeHeader(): ?HeaderInterface
    {
        return $this->contentType;
    }
}
