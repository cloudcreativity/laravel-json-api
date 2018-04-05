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

namespace CloudCreativity\JsonApi\Object;

use CloudCreativity\JsonApi\Contracts\Object\DocumentInterface;
use CloudCreativity\JsonApi\Document\Error;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\Utils\Object\StandardObject;
use CloudCreativity\Utils\Object\StandardObjectInterface;

/**
 * Class Document
 *
 * @package CloudCreativity\JsonApi
 */
class Document extends StandardObject implements DocumentInterface
{

    use MetaMemberTrait;

    /**
     * @inheritdoc
     */
    public function getData()
    {
        if (!$this->has(self::DATA)) {
            throw new RuntimeException('Data member is not present.');
        }

        $data = $this->get(self::DATA);

        if (is_array($data) || is_null($data)) {
            return $data;
        }

        if ($data instanceof StandardObjectInterface) {
            throw new RuntimeException('Data member is not an object or null.');
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function getResource()
    {
        $data = $this->{self::DATA};

        if (!is_object($data)) {
            throw new RuntimeException('Data member is not an object.');
        }

        return new ResourceObject($data);
    }

    /**
     * @inheritDoc
     */
    public function getResources()
    {
        $data = $this->get(self::DATA);

        if (!is_array($data)) {
            throw new RuntimeException('Data member is not an array.');
        }

        return ResourceObjectCollection::create($data);
    }

    /**
     * @inheritdoc
     */
    public function getRelationship()
    {
        return new Relationship($this->proxy);
    }

    /**
     * @inheritDoc
     */
    public function getIncluded()
    {
        if (!$this->has(self::INCLUDED)) {
            return null;
        }

        if (!is_array($data = $this->{self::INCLUDED})) {
            throw new RuntimeException('Included member is not an array.');
        }

        return ResourceObjectCollection::create($data);
    }

    /**
     * @inheritDoc
     */
    public function getErrors()
    {
        if (!$this->has(self::ERRORS)) {
            return null;
        }

        if (!is_array($data = $this->{self::ERRORS})) {
            throw new RuntimeException('Errors member is not an array.');
        }

        return Error::createMany($data);
    }

}
