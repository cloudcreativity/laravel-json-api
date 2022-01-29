<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Encoder\Neomerx;

use CloudCreativity\LaravelJsonApi\Codec\Codec;
use CloudCreativity\LaravelJsonApi\Codec\Decoding;
use CloudCreativity\LaravelJsonApi\Codec\Encoding;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Document\Error\Error;
use CloudCreativity\LaravelJsonApi\Document\Link\Link;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

/**
 * Class Factory
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Factory
{

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * Factory constructor.
     *
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Create an error.
     *
     * @param Error $error
     * @return ErrorInterface
     */
    public function createError(Error $error): ErrorInterface
    {
        $about = $error->getLinks()[DocumentInterface::KEYWORD_ERRORS_ABOUT] ?? null;

        return $this->factory->createError(
            $error->getId(),
            $about ? $this->createLink($about) : null,
            $error->getStatus(),
            $error->getCode(),
            $error->getTitle(),
            $error->getDetail(),
            $error->getSource(),
            $error->getMeta()
        );
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

        return collect($errors)->map(function ($error) {
            return ($error instanceof ErrorInterface) ? $error : $this->createError(Error::cast($error));
        })->all();
    }

    /**
     * Create a link.
     *
     * @param Link $link
     * @return LinkInterface
     */
    public function createLink(Link $link): LinkInterface
    {
        return $this->factory->createLink(
            $link->getHref(),
            $link->getMeta(),
            true
        );
    }

    /**
     * @param ContainerInterface $container
     * @param Encoding $encoding
     * @param Decoding|null $decoding
     * @return Codec
     */
    public function createCodec(ContainerInterface $container, Encoding $encoding, ?Decoding $decoding): Codec
    {
        return new Codec($this->factory, $container, $encoding, $decoding);
    }
}
