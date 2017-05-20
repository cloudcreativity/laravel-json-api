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

namespace App\Http\Controllers;

/**
 * Class PostsController
 */
class PostsController
{

    /**
     * @return string
     */
    public function index()
    {
        return 'posts:index';
    }

    /**
     * @return string
     */
    public function create()
    {
        return 'posts:create';
    }

    /**
     * @param $id
     * @return string
     */
    public function read($id)
    {
        return "posts:read:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function update($id)
    {
        return "posts:update:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function delete($id)
    {
        return "posts:delete:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function readRelatedResource($id)
    {
        return "posts:read-related:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function readRelationship($id)
    {
        return "posts:read-relationship:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function replaceRelationship($id)
    {
        return "posts:replace-relationship:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function addToRelationship($id)
    {
        return "posts:add-relationship:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function removeFromRelationship($id)
    {
        return "posts:remove-relationship:$id";
    }
}
