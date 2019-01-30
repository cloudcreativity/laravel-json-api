<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

namespace DummyApp\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use CloudCreativity\LaravelJsonApi\Http\Requests\FetchResource;
use DummyApp\Jobs\SharePost;
use DummyApp\Post;
use Illuminate\Http\Response;

class PostsController extends JsonApiController
{

    /**
     * @param FetchResource $request
     * @param Post $post
     * @return Response
     */
    public function share(FetchResource $request, Post $post): Response
    {
        SharePost::dispatch($post);

        return $this->reply()->content($post);
    }
}
