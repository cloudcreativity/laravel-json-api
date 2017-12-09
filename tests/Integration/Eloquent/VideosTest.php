<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Models\Video;

class VideosTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'videos';

    public function testCreateWithClientId()
    {
        $video = factory(Video::class)->make();

        $data = [
            'type' => 'videos',
            'id' => $video->getKey(),
            'attributes' => [
                'title' => $video->title,
                'description' => $video->description,
            ],
        ];

        $expected = $data;
        $expected['relationships'] = [
            'uploaded-by' => [
                'data' => [
                    'type' => 'users',
                    'id' => $video->user_id,
                ],
            ],
        ];

        $this->actingAs($video->user);

        $this->expectSuccess()
            ->doCreate($data)
            ->assertCreated($expected);

        $this->assertModelCreated($video, $video->getKey());
    }

    public function testCreateWithInvalidClientId()
    {
        $this->markTestIncomplete('@todo when it is possible to validate client ids.');
    }

    public function testRead()
    {
        $video = factory(Video::class)->create();

        $expected = [
            'type' => 'videos',
            'id' => $video->getKey(),
            'attributes' => [
                'title' => $video->title,
                'description' => $video->description,
            ],
            'relationships' => [
                'uploaded-by' => [
                    'data' => [
                        'type' => 'users',
                        'id' => $video->user_id,
                    ],
                ],
            ],
        ];

        $this->expectSuccess()
            ->doRead($video)
            ->assertRead($expected);
    }
}
