<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

class AsyncTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'downloads';

    public function testCreate()
    {
        $data = [
            'type' => 'downloads',
            'attributes' => [
                'category' => 'my-posts',
            ],
        ];

        $this->doCreate($data)->assertStatus(202)->assertJson([
            'data' => [
                'type' => 'queue-jobs',
            ],
        ]);
    }
}
