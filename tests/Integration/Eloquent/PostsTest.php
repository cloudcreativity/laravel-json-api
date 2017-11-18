<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Models\Comment;
use CloudCreativity\LaravelJsonApi\Tests\Models\Post;
use CloudCreativity\LaravelJsonApi\Tests\Models\Tag;
use Illuminate\Foundation\Auth\User;

class PostsTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * Test searching with a sort parameter.
     */
    public function testSortedSearch()
    {
        $a = factory(Post::class)->create([
            'title' => 'Title A',
        ]);

        $b = factory(Post::class)->create([
            'title' => 'Title B',
        ]);

        $response = $this->doSearch(['sort' => '-title']);
        $response->assertSearchResponse()->assertContainsOnly(['posts' => [$a->getKey(), $b->getKey()]]);

        $json = $response->decodeResponseJson();
        $actual = [array_get($json, 'data.0.id'), array_get($json, 'data.1.id')];
        $this->assertEquals([$b->getKey(), $a->getKey()], $actual);
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
        $model->tags()->create(['name' => 'Important']);;

        $this->doRead($model)->assertReadResponse($this->serialize($model));
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

        $this->doReadRelationship($model, 'author')
            ->assertReadHasOneIdentifier('users', $model->author_id);
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
     * Test that we can change the related author to a different resource.
     */
    public function testReplaceAuthorRelationship()
    {
        $post = $this->createPost();
        /** @var User $author */
        $author = factory(User::class)->create();

        $data = ['type' => 'users', 'id' => (string) $author->getKey()];

        $this->doReplaceRelationship($post, 'author', $data)
            ->assertStatus(204);

        $this->assertModelPatched($post, ['author_id' => $author->getKey()]);
    }

    /**
     * Test that we can clear the related author relationship.
     */
    public function testReplaceAuthorRelationshipWithNull()
    {
        $post = $this->createPost();

        $this->doReplaceRelationship($post, 'author', null)
            ->assertStatus(204);

        $this->assertModelPatched($post, ['author_id' => null]);
    }

    /**
     * Test that we can attach related resources to an empty has-many relationship.
     */
    public function testReplaceEmptyTagsRelationship()
    {
        $post = $this->createPost();
        $tags = factory(Tag::class, 2)->create();

        $data = $tags->map(function (Tag $tag) {
            return ['type' => 'tags', 'id' => (string) $tag->getKey()];
        })->all();

        $this->doReplaceRelationship($post, 'tags', $data)
            ->assertStatus(204);

        $this->assertSame($post->tags()->count(), 2);
    }

    /**
     * Test that we can clear related resources from a has-many relationship.
     */
    public function testReplaceTagsRelationshipWithNone()
    {
        $post = $this->createPost();
        $tags = factory(Tag::class, 2)->create();
        $post->tags()->sync($tags);

        $this->doReplaceRelationship($post, 'tags', [])
            ->assertStatus(204);

        $this->assertSame($post->tags()->count(), 0);
    }

    /**
     * Test that we can add resources to a has-many relationship.
     */
    public function testAddToTagsRelationship()
    {
        $post = $this->createPost();
        $existing = factory(Tag::class, 2)->create();
        $post->tags()->sync($existing);

        $add = factory(Tag::class, 2)->create();
        $data = $add->map(function (Tag $tag) {
            return ['type' => 'tags', 'id' => (string) $tag->getKey()];
        })->all();

        $this->doAddToRelationship($post, 'tags', $data)
            ->assertStatus(204);

        $this->assertSame($post->tags()->count(), 4);
    }

    /**
     * Test that we can remove resources from a has-many relationship.
     */
    public function testRemoveFromTagsRelationship()
    {
        $post = $this->createPost();
        $tags = factory(Tag::class, 4)->create();
        $post->tags()->sync($tags);

        $data = $tags->take(2)->map(function (Tag $tag) {
            return ['type' => 'tags', 'id' => (string) $tag->getKey()];
        })->all();

        $this->doRemoveFromRelationship($post, 'tags', $data)
            ->assertStatus(204);

        $this->assertSame($post->tags()->count(), 2);
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

    /**
     * Get the posts resource that we expect in server responses.
     *
     * @param Post $model
     * @return array
     */
    private function serialize(Post $model)
    {
        return [
            'type' => 'posts',
            'id' => $id = (string) $model->getKey(),
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
                    'data' => $model->tags->map(function (Tag $tag) {
                        return ['type' => 'tags', 'id' => (string) $tag->getKey()];
                    })->all(),
                ],
                'comments' => [
                    'links' => [
                        'self' => "http://localhost/api/v1/posts/$id/relationships/comments",
                        'related' => "http://localhost/api/v1/posts/$id/comments",
                    ],
                ],
            ],
        ];
    }
}
