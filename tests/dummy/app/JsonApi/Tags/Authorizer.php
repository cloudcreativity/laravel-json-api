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

namespace DummyApp\JsonApi\Tags;

use CloudCreativity\LaravelJsonApi\Auth\AbstractAuthorizer;
use DummyApp\Tag;
use DummyApp\User;

class Authorizer extends AbstractAuthorizer
{

    /**
     * @inheritdoc
     */
    public function index($type, $request)
    {
        if (Tag::class !== $type) {
            throw new \InvalidArgumentException("Unexpected type: $type");
        }

        $this->authenticate();
    }

    /**
     * @inheritdoc
     */
    public function create($type, $request)
    {
        if (Tag::class !== $type) {
            throw new \InvalidArgumentException("Unexpected type: $type");
        }

        $this->can('author', User::class);
    }

    /**
     * @inheritdoc
     */
    public function read($record, $request)
    {
        if (!$record instanceof Tag) {
            throw new \InvalidArgumentException("Expecting a tag.");
        }

        $this->authenticate();
    }

    /**
     * @inheritdoc
     */
    public function update($record, $request)
    {
        if (!$record instanceof Tag) {
            throw new \InvalidArgumentException("Expecting a tag.");
        }

        $this->can('admin', User::class);
    }

    /**
     * @inheritdoc
     */
    public function delete($record, $request)
    {
        if (!$record instanceof Tag) {
            throw new \InvalidArgumentException("Expecting a tag.");
        }

        $this->update($record, $request);
    }

}
