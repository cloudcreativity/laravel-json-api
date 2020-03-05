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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Tests\Integration\Http\Controllers\TestEvent;
use DummyApp\Post;
use Illuminate\Support\Arr;

class BroadcastingTest extends TestCase
{

    public function testBroadcastWith()
    {
        $post = factory(Post::class)->create();
        $event = new TestEvent('created', $post);
        $data = $event->broadcastWith();

        $this->assertSame('posts', Arr::get($data, 'data.type'));
        $this->assertEquals($post->getRouteKey(), Arr::get($data, 'data.id'));
        $this->assertSame(url('/api/v1/posts', $post), Arr::get($data, 'data.links.self'));
    }
}
