<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use App\Post;
use Package\Blog;
use Package\ResourceProvider;

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
            'id' => $blog->getKey(),
            'attributes' => [
                'title' => $blog->title,
                'article' => $blog->article,
                'published-at' => $blog->published_at->toW3cString(),
            ],
        ];

        $this->doRead($blog)->assertRead($expected);
    }

    /**
     * Test that we can read a resource from the application.
     */
    public function testReadPost()
    {
        $this->resourceType = 'posts';

        /** @var Post $post */
        $post = factory(Post::class)->create();

        $expected = [
            'type' => 'posts',
            'id' => $post->getKey(),
        ];

        $this->doRead($post)->assertRead($expected);
    }
}
