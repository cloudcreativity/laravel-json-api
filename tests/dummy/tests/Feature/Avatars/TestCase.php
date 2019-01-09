<?php

namespace DummyApp\Tests\Feature\Avatars;

use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase as BaseTestCase;
use DummyApp\Avatar;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'avatars';

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        Storage::fake('local');
    }

    /**
     * @return array
     */
    public function fieldProvider(): array
    {
        return [
            'created-at' => ['created-at'],
            'media-type' => ['media-type'],
            'updated-at' => ['updated-at'],
            'user' => ['user'],
        ];
    }

    /**
     * Get the expected JSON API resource for the avatar model.
     *
     * @param Avatar $avatar
     * @return ResourceObject
     */
    protected function serialize(Avatar $avatar): ResourceObject
    {
        $self = url("/api/v1/avatars", $avatar);

        return ResourceObject::create([
            'type' => 'avatars',
            'id' => (string) $avatar->getRouteKey(),
            'attributes' => [
                'created-at' => $avatar->created_at->toAtomString(),
                'media-type' => $avatar->media_type,
                'updated-at' => $avatar->updated_at->toAtomString(),
            ],
            'relationships' => [
                'user' => [
                    'links' => [
                        'self' => "{$self}/relationships/user",
                        'related' => "{$self}/user",
                    ],
                ],
            ],
            'links' => [
                'self' => $self,
            ],
        ]);
    }
}
