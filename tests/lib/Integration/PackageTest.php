<?php
/**
 * Copyright 2018 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use DummyApp\Post;
use DummyPackage\Blog;

class PackageTest extends TestCase
{

    /**
     * Test that we can read a resource from the package.
     */
    public function testReadBlog()
    {
        $this->resourceType = 'blogs';

        /** @var Blog $blog */
        $blog = factory(Blog::class)->states('published')->create();

        $expected = [
            'type' => 'blogs',
            'id' => $blog,
            'attributes' => [
                'title' => $blog->title,
                'article' => $blog->article,
                'published-at' => $blog->published_at->toW3cString(),
            ],
        ];

        $this->doRead($blog)->assertFetchedOne($expected);
    }

    /**
     * Test that we can read a resource from the application.
     */
    public function testReadPost()
    {
        $this->resourceType = 'posts';

        /** @var Post $post */
        $post = factory(Post::class)->create();

        $this->doRead($post)->assertFetchedOne($post);
    }
}
