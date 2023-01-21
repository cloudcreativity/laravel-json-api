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

namespace CloudCreativity\LaravelJsonApi\Http\Headers;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderInterface;
use InvalidArgumentException;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;

class Header implements HeaderInterface
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var MediaTypeInterface[]
     */
    private array $mediaTypes;

    /**
     * Header constructor.
     *
     * @param string $name
     * @param MediaTypeInterface[] $mediaTypes
     */
    public function __construct(string $name, array $mediaTypes)
    {
        foreach ($mediaTypes as $mediaType) {
            if (!$mediaType instanceof MediaTypeInterface) {
                throw new InvalidArgumentException('Expecting only media type objects.');
            }
        }

        $this->name = $name;
        $this->mediaTypes = $mediaTypes;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getMediaTypes(): array
    {
        return $this->mediaTypes;
    }
}