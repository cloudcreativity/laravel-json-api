<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

namespace DummyApp\JsonApi;

use CloudCreativity\LaravelJsonApi\Auth\AbstractAuthorizer;

class GenericAuthorizer extends AbstractAuthorizer
{

    /**
     * @inheritDoc
     */
    public function index($type, $request)
    {
        $this->can('access', $type);
    }

    /**
     * @inheritDoc
     */
    public function create($type, $request)
    {
        $this->can('create', $type);
    }

    /**
     * @inheritDoc
     */
    public function read($record, $request)
    {
        $this->can('read', $record);
    }

    /**
     * @inheritDoc
     */
    public function update($record, $request)
    {
        $this->can('update', $record);
    }

    /**
     * @inheritDoc
     */
    public function delete($record, $request)
    {
        $this->can('delete', $record);
    }

}
