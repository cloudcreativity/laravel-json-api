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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Issue566;

use CloudCreativity\LaravelJsonApi\Document\Error\Error;
use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use CloudCreativity\LaravelJsonApi\Exceptions\JsonApiException;
use DummyApp\JsonApi\Posts\Adapter as PostAdapter;
use DummyApp\Post;
use Illuminate\Http\Response;

class Adapter extends PostAdapter
{

    /**
     * @param Post $post
     * @param ResourceObject $resource
     * @throws JsonApiException
     */
    protected function creating(Post $post, ResourceObject $resource): void
    {
        $error = Error::fromArray([
            'title'     => 'The language you want to use is not active',
            'status'    => Response::HTTP_UNPROCESSABLE_ENTITY,
        ]);

        throw new JsonApiException($error);
    }
}
