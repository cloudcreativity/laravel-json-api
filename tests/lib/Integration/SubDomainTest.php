<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use DummyApp\Post;
use DummyApp\User;
use Illuminate\Support\Facades\Route;

/**
 * Class SubDomainTest
 *
 * Tests routing when there is a route parameter before the JSON API route
 * parameters, in this case a wildcard domain. We need to test that this
 * does not affect the JSON API controller from obtaining route parameters.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class SubDomainTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    public function testRead()
    {
        $post = factory(Post::class)->create();

        $this->doRead($post)->assertRead([
            'type' => 'posts',
            'id' => (string) $post->getKey(),
            'links' => [
                'self' => "http://foo.example.com/api/posts/{$post->getKey()}",
            ],
        ]);
    }

    public function testUpdate()
    {
        $post = factory(Post::class)->create();

        $this->doUpdate([
            'type' => 'posts',
            'id' => (string) $post->getKey(),
            'attributes' => [
                'title' => 'Hello World',
            ],
        ])->assertStatus(200);
    }

    public function testDelete()
    {
        $post = factory(Post::class)->create();

        $this->doDelete($post)->assertStatus(204);
    }

    public function testReadRelated()
    {
        $post = factory(Post::class)->create();

        $this->doReadRelated($post, 'author')->assertStatus(200);
    }

    public function testReadRelationship()
    {
        $post = factory(Post::class)->create();

        $this->doReadRelationship($post, 'author')->assertStatus(200);
    }

    public function testReplaceRelationship()
    {
        $post = factory(Post::class)->create();
        $user = factory(User::class)->create();

        $this->doReplaceRelationship($post, 'author', [
            'type' => 'users',
            'id' => (string) $user->getKey()
        ])->assertStatus(204);
    }

    /**
     * @return $this|void
     */
    protected function withAppRoutes()
    {
        Route::group([
            'domain' => '{wildcard}.example.com',
        ], function () {
            parent::withAppRoutes();
        });
    }

    /**
     * @param array $params
     * @return array
     */
    protected function addDefaultRouteParams(array $params)
    {
        $params['wildcard'] = 'foo';

        return $params;
    }
}
