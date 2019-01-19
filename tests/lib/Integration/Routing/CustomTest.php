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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Routing;

use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;
use DummyApp\Comment;
use DummyApp\Jobs\SharePost;
use DummyApp\Post;
use Illuminate\Support\Facades\Queue;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;

class CustomTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * @var bool
     */
    protected $appRoutes = false;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        Queue::fake();

        $this->withFluentRoutes()->routes(function (RouteRegistrar $api) {
            $api->get('/', 'HomeController@index');

            $api->resource('posts')->controller()->routes(function (RouteRegistrar $posts) {
                $posts->get('version', 'HomeController@index');
                $posts->post('{record}/share', 'share');
            });

            $api->resource('comments')->controller()->routes(function (RouteRegistrar $comments) {
                $comments->field('post')->post('{record}/post/share', 'sharePost');
            });
        });
    }

    /**
     * @return array
     */
    public function versionProvider(): array
    {
        return [
            'root' => ['/api/v1'],
            'resource' => ['/api/v1/posts/version'],
        ];
    }

    /**
     * @param string $uri
     * @dataProvider versionProvider
     */
    public function testVersion(string $uri): void
    {
        $this->getJsonApi($uri)->assertMetaWithoutData([
            'version' => 'v1',
        ]);
    }

    /**
     * @param string $uri
     * @dataProvider versionProvider
     */
    public function testVersionContentIsNegotiated(string $uri): void
    {
        $expected = ['message' =>
            "The requested resource is capable of generating only content not acceptable "
            . "according to the Accept headers sent in the request."
        ];

        $this->get($uri, ['Accept' => 'application/json'])
            ->assertStatus(406)
            ->assertExactJson($expected);
    }

    public function testResource(): void
    {
        $post = factory(Post::class)->create();
        $uri = url('/api/v1/posts', [$post, 'share']);

        $this->postJsonApi($uri, ['include' => 'author'])
            ->assertFetchedOne($post)
            ->assertIsIncluded('users', $post->author);

        Queue::assertPushed(SharePost::class, function ($job) use ($post) {
            return $post->is($job->post);
        });
    }

    public function testResourceNotFound(): void
    {
        $this->postJsonApi('/api/v1/posts/999/share')->assertErrorStatus([
            'status' => '404',
            'title' => 'Not Found',
        ]);
    }

    public function testResourceContentIsNegotiated(): void
    {
        $expected = ['message' =>
            "The requested resource is capable of generating only content not acceptable "
            . "according to the Accept headers sent in the request."
        ];

        $post = factory(Post::class)->create();
        $uri = url('/api/v1/posts', [$post, 'share']);

        $this->post($uri, [], ['Accept' => 'application/json'])
            ->assertStatus(406)
            ->assertExactJson($expected);
    }

    public function testResourceValidated(): void
    {
        $post = factory(Post::class)->create();
        $uri = url('/api/v1/posts', [$post, 'share']);

        $expected = [
            'status' => '400',
            'source' => ['parameter' => 'include'],
        ];

        $this->postJsonApi($uri, ['include' => 'foo'])
            ->assertErrorStatus($expected);
    }

    public function testRelationship(): void
    {
        $comment = factory(Comment::class)->states('post')->create();
        $post = $comment->commentable;
        $uri = url('/api/v1/comments', [$comment, 'post', 'share']);

        $this->postJsonApi($uri, ['include' => 'author'])
            ->assertFetchedOne($post)
            ->assertIsIncluded('users', $post->author);

        Queue::assertPushed(SharePost::class, function ($job) use ($post) {
            return $post->is($job->post);
        });
    }

    public function testRelationshipNotFound(): void
    {
        $this->postJsonApi('/api/v1/comments/999/post/share')->assertErrorStatus([
            'status' => '404',
            'title' => 'Not Found',
        ]);
    }

    public function testRelationshipContentIsNegotiated(): void
    {
        $expected = ['message' =>
            "The requested resource is capable of generating only content not acceptable "
            . "according to the Accept headers sent in the request."
        ];

        $comment = factory(Comment::class)->states('post')->create();
        $uri = url('/api/v1/comments', [$comment, 'post', 'share']);

        $this->post($uri, [], ['Accept' => 'application/json'])
            ->assertStatus(406)
            ->assertExactJson($expected);
    }

    public function testRelationshipValidated(): void
    {
        $comment = factory(Comment::class)->states('post')->create();
        $uri = url('/api/v1/comments', [$comment, 'post', 'share']);

        $expected = [
            'status' => '400',
            'source' => ['parameter' => 'include'],
        ];

        $this->postJsonApi($uri, ['include' => 'foo'])
            ->assertErrorStatus($expected);
    }
}
