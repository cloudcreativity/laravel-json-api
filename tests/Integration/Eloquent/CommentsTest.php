<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Models\Comment;

class CommentsTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'comments';

    public function testCreate()
    {
        $model = $this->createComment(false);

        $data = [
            'type' => 'comments',
            'id' => $id = $model->getKey(), // client generated id.
            'attributes' => [
                'content' => $model->content,
            ],
            'relationships' => [
                'post' => [
                    'data' => [
                        'type' => 'posts',
                        'id' => (string) $model->post_id,
                    ],
                ],
            ],
        ];

        $this->actingAs($model->user);
        $this->doCreate($data)->assertCreateResponse($data);
        $this->assertNotNull(Comment::find($id));
    }

    public function testCreateWithInvalidClientId()
    {
        $this->markTestIncomplete('@todo when it is possible to validate client ids.');
    }

    public function testRead()
    {
        $model = $this->createComment();

        $data = [
            'type' => 'comments',
            'id' => (string) $model->getKey(),
            'attributes' => [
                'content' => $model->content,
            ],
            'relationships' => [
                'post' => [
                    'data' => [
                        'type' => 'posts',
                        'id' => $model->post_id,
                    ],
                ],
                'created-by' => [
                    'data' => [
                        'type' => 'users',
                        'id' => $model->user_id,
                    ],
                ],
            ],
        ];

        $this->doRead($model)->assertReadResponse($data);
    }

    /**
     * @return mixed
     */
    protected function getResourceType()
    {
        return 'comments';
    }

    /**
     * @param bool $create
     * @return Comment
     */
    private function createComment($create = true)
    {
        $factory = factory(Comment::class);

        return $create ? $factory->create() : $factory->make();
    }
}
