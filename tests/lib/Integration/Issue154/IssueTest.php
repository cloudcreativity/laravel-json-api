<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Issue154;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Http\Controllers\PostsController;
use DummyApp\Post;

class IssueTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * @return array
     */
    public function createProvider()
    {
        return [
            ['saving', ['creating', 'saved', 'created']],
            ['creating', ['saved', 'created']],
            ['created', ['saved']],
            ['saved', []],
        ];
    }

    /**
     * @param $hook
     * @param array $unexpected
     * @dataProvider createProvider
     */
    public function testCreate($hook, array $unexpected)
    {
        $post = factory(Post::class)->make();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $post->author_id,
                    ],
                ],
            ],
        ];



        $this->withResponse($hook, $unexpected)->doCreate($data)->assertStatus(202);
    }

    /**
     * @return array
     */
    public function updateProvider()
    {
        return [
            ['saving', ['updating', 'saved', 'updated']],
            ['updating', ['saved', 'updated']],
            ['updated', ['saved']],
            ['saved', []],
        ];
    }

    /**
     * @param $hook
     * @param array $unexpected
     * @dataProvider updateProvider
     */
    public function testUpdate($hook, array $unexpected)
    {
        $post = factory(Post::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getKey(),
            'attributes' => [
                'title' => 'My First Post',
            ],
        ];

        $this->withResponse($hook, $unexpected)->doUpdate($data)->assertStatus(202);
    }

    /**
     * @return array
     */
    public function deleteProvider()
    {
        return [
            ['deleting', ['deleted']],
            ['deleted', []],
        ];
    }

    /**
     * @param $hook
     * @param array $unexpected
     * @dataProvider deleteProvider
     */
    public function testDelete($hook, array $unexpected)
    {
        $post = factory(Post::class)->create();

        $this->withResponse($hook, $unexpected)->doDelete($post)->assertStatus(202);
    }

    /**
     * @param $hook
     * @param array $unexpected
     *      hooks that must not be invoked.
     * @return $this
     */
    private function withResponse($hook, array $unexpected = [])
    {
        $this->app->instance(PostsController::class, $controller = new Controller());
        $controller->responses[$hook] = response('', 202);
        $controller->unexpected = $unexpected;

        return $this;
    }
}
