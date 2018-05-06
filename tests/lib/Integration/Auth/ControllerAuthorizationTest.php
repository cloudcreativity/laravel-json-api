<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Auth;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Post;

class ControllerAuthorizationTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'comments';

    /**
     * @var array
     */
    private $data;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $post = factory(Post::class)->create();

        $this->data = [
            'type' => 'comments',
            'attributes' => [
                'content' => '...'
            ],
            'relationships' => [
                'commentable' => [
                    'data' => [
                        'type' => 'posts',
                        'id' => (string) $post->getKey(),
                    ],
                ],
            ],
        ];
    }

    public function testCreateUnauthenticated()
    {
        $this->doCreate($this->data)->assertStatus(401)->assertExactJson([
            'errors' => [
                [
                    'title' => 'Unauthenticated',
                    'status' => '401',
                ],
            ],
        ]);
    }

    public function testCreateUnauthorized()
    {
        $this->actingAsUser('admin')->doCreate($this->data)->assertStatus(403)->assertExactJson([
            'errors' => [
                [
                    'title' => 'Unauthorized',
                    'status' => '403',
                ],
            ],
        ]);
    }

    public function testCreateAllowed()
    {
        $this->actingAsUser()->doCreate($this->data)->assertStatus(201);
    }
}
