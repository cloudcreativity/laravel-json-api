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

namespace CloudCreativity\LaravelJsonApi\Document;

use CloudCreativity\LaravelJsonApi\Document\Error\Error;
use CloudCreativity\LaravelJsonApi\Document\Link\Link;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\DocumentInterface;
use Neomerx\JsonApi\Contracts\Schema\ErrorInterface;
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;
use Neomerx\JsonApi\Schema\ErrorCollection;

class Mapper
{
    /**
     * @var FactoryInterface
     */
    private FactoryInterface $factory;

    /**
     * Mapper constructor.
     *
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Map a Laravel JSON:API error to a Neomerx error.
     *
     * @param Error $error
     * @return ErrorInterface
     */
    public function createError(Error $error): ErrorInterface
    {
        $about = $error->getLinks()[DocumentInterface::KEYWORD_ERRORS_ABOUT] ?? null;
        $meta = $error->getMeta();

        return new \Neomerx\JsonApi\Schema\Error(
            $error->getId(),
            $about ? $this->createLink($about) : null,
            null,
            $error->getStatus(),
            $error->getCode(),
            $error->getTitle(),
            $error->getDetail(),
            $error->getSource(),
            !empty($meta),
            $meta,
        );
    }

    /**
     * Cast an error to a Neomerx error.
     *
     * @param ErrorInterface|Error|array $error
     * @return ErrorInterface
     */
    public function castError($error): ErrorInterface
    {
        if ($error instanceof ErrorInterface) {
            return $error;
        }

        return $this->createError(Error::cast($error));
    }

    /**
     * Create an error collection.
     *
     * @param iterable $errors
     * @return ErrorInterface[]
     */
    public function createErrors(iterable $errors): array
    {
        if ($errors instanceof ErrorCollection) {
            return $errors->getArrayCopy();
        }

        $converted = [];

        foreach ($errors as $error) {
            $converted[] = $this->castError($error);
        }

        return $converted;
    }

    /**
     * Map a Laravel JSON:API link to a Neomerx link.
     *
     * @param Link $link
     * @return LinkInterface
     */
    private function createLink(Link $link): LinkInterface
    {
        $meta = $link->getMeta();

        return $this->factory->createLink(
            false,
            $link->getHref(),
            !empty($meta),
            $meta,
        );
    }
}