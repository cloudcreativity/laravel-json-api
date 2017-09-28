<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Models\Comment;
use CloudCreativity\LaravelJsonApi\Tests\Models\Post;
use CloudCreativity\LaravelJsonApi\Tests\Models\Tag;

class PostsTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    public function testSearch()
    {
        factory(Post::class, 3)->create();

        $this->doSearch(['sort' => '-created-at'])->assertSearchResponse();
    }

    /**
     * Test that we can search posts for specific ids
     */
    public function testSearchById()
    {
        $models = factory(Post::class, 2)->create();
        // this model should not be in the search results
        $this->createPost();

        $this->doSearchById($models)
            ->assertSearchByIdResponse($models);
    }

    /**
     * Test the create resource route.
     */
    public function testCreate()
    {
        /** @var Tag $tag */
        $tag = factory(Tag::class)->create();
        $model = $this->createPost(false);

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $model->title,
                'slug' => $model->slug,
                'content' => $model->content,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $model->author_id,
                    ],
                ],
                'tags' => [
                    'data' => [
                        [
                            'type' => 'tags',
                            'id' => (string) $tag->getKey(),
                        ],
                    ],
                ],
            ],
        ];

        $id = $this
            ->doCreate($data)
            ->assertCreateResponse($data);

        $this->assertModelCreated($model, $id, ['title', 'slug', 'content', 'author_id']);
    }

    /**
     * Test the read resource route.
     */
    public function testRead()
    {
        $model = $this->createPost();
        /** @var Tag $tag */
        $tag = $model->tags()->create(['name' => 'Important']);

        $data = [
            'type' => 'posts',
            'id' => (string) $model->getKey(),
            'attributes' => [
                'title' => $model->title,
                'slug' => $model->slug,
                'content' => $model->content,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $model->author_id,
                    ],
                ],
                'tags' => [
                    'data' => [
                        [
                            'type' => 'tags',
                            'id' => (string) $tag->getKey(),
                        ],
                    ],
                ],
            ],
        ];

        $this->doRead($model)->assertReadResponse($data);
    }

    /**
     * Test the update resource route.
     */
    public function testUpdate()
    {
        $model = $this->createPost();

        $data = [
            'type' => 'posts',
            'id' => (string) $model->getKey(),
            'attributes' => [
                'slug' => 'posts-test',
                'title' => 'Foo Bar Baz Bat',
            ],
        ];

        $this->doUpdate($data)->assertUpdateResponse($data);
        $this->assertModelPatched($model, $data['attributes'], ['content']);
    }

    /**
     * Test the delete resource route.
     */
    public function testDelete()
    {
        $model = $this->createPost();

        $this->doDelete($model)->assertDeleteResponse();
        $this->assertModelDeleted($model);
    }

    /**
     * Test that we can read the related author.
     */
    public function testReadAuthor()
    {
        $model = $this->createPost();
        $author = $model->author;

        $data = [
            'type' => 'users',
            'id' => $author->getKey(),
            'attributes' => [
                'name' => $author->name,
            ],
        ];

        $this->doReadRelated($model, 'author')->assertReadHasOne($data);
    }

    /**
     * Test that we can read the related comments.
     */
    public function testReadComments()
    {
        $model = $this->createPost();
        $comments = factory(Comment::class, 2)->create(['post_id' => $model->getKey()]);
        /** This comment shouldn't appear in the results... */
        factory(Comment::class)->create();

        $this->doReadRelated($model, 'comments')->assertReadHasMany('comments', $comments);
    }

    /**
     * Test that we can read the resource identifier for the related author.
     */
    public function testReadAuthorRelationship()
    {
        $model = $this->createPost();

        $this->doReadRelationship($model, 'author')->assertReadHasOneIdentifier('users', $model->author_id);
    }

    /**
     * Test that we can read the resource identifiers for the related comments.
     */
    public function testReadCommentsRelationship()
    {
        $model = $this->createPost();
        $comments = factory(Comment::class, 2)->create(['post_id' => $model->getKey()]);
        /** This comment shouldn't appear in the results... */
        factory(Comment::class)->create();

        $this->doReadRelated($model, 'comments')->assertReadHasManyIdentifiers('comments', $comments);
    }

    /**
     * Just a helper method so that we get a type-hinted model back...
     *
     * @param bool $create
     * @return Post
     */
    private function createPost($create = true)
    {
        $builder = factory(Post::class);

        return $create ? $builder->create() : $builder->make();
    }
}
