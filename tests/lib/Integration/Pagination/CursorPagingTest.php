<?php
/*
 * Copyright 2022 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Pagination;

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Pagination\CursorStrategy;
use DummyApp\Comment;
use Faker\Generator;
use Illuminate\Database\Eloquent\Collection;

class CursorPagingTest extends TestCase
{

    /**
     * @var Generator
     */
    private $faker;

    /**
     * @var CursorStrategy
     */
    private $strategy;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = $this->app->make(Generator::class);
        /** Allow us to override settings in this test. */
        $this->strategy = $this->app->make(CursorStrategy::class);
        $this->app->instance(CursorStrategy::class, $this->strategy);
    }

    public function testNoPages()
    {
        $links = [
            'first' => $this->buildLink(
                '/api/v1/comments',
                [
                    'page' => [
                        'limit' => 10,
                    ],
                ]
            ),
        ];

        $page = [
            'per-page' => 10,
            'from' => null,
            'to' => null,
            'has-more' => false,
        ];

        $response = $this
            ->actingAsUser()
            ->jsonApi()
            ->page(['limit' => 10])
            ->get('/api/v1/comments');

        $response
            ->assertFetchedNone()
            ->assertExactMeta(compact('page'))
            ->assertExactLinks($links);
    }

    public function testOnlyLimit()
    {
        /** @var Collection $comments */
        $comments = factory(Comment::class, 5)->create([
            'created_at' => function () {
                return $this->faker->dateTime();
            }
        ])->sortByDesc('created_at')->values();

        $meta = [
            'page' => [
                'per-page' => 4,
                'from' => (string) $comments->first()->getRouteKey(),
                'to' => (string) $comments->get(3)->getRouteKey(),
                'has-more' => true,
            ],
        ];

        $response = $this
            ->actingAsUser()
            ->jsonApi('comments')
            ->page(['limit' => 4])
            ->get('/api/v1/comments');

        $response
            ->assertFetchedMany($comments->take(4))
            ->assertExactMeta($meta)
            ->assertExactLinks($this->createLinks(4, $comments->first(), $comments->get(3)));
    }

    public function testBefore()
    {
        /** @var Collection $comments */
        $comments = factory(Comment::class, 10)->create([
            'created_at' => function () {
                return $this->faker->dateTime();
            }
        ])->sortByDesc('created_at')->values();

        $page = [
            'limit' => '3',
            'before' => $comments->get(7)->getRouteKey(),
        ];

        $expected = collect([
            $comments->get(4),
            $comments->get(5),
            $comments->get(6),
        ]);

        $meta = [
            'page' => [
                'per-page' => 3,
                'from' => (string) $expected->first()->getRouteKey(),
                'to' => (string) $expected->last()->getRouteKey(),
                'has-more' => true,
            ],
        ];

        $response = $this
            ->actingAsUser()
            ->jsonApi('comments')
            ->page($page)
            ->get('/api/v1/comments');

        $response
            ->assertFetchedMany($expected)
            ->assertExactMeta($meta)
            ->assertExactLinks($this->createLinks(3, $expected->first(), $expected->last()));
    }

    public function testBeforeAscending()
    {
        $this->strategy->withAscending();

        /** @var Collection $comments */
        $comments = factory(Comment::class, 10)->create([
            'created_at' => function () {
                return $this->faker->dateTime();
            }
        ])->sortBy('created_at')->values();

        $page = [
            'limit' => '3',
            'before' => $comments->get(7)->getRouteKey(),
        ];

        $expected = collect([
            $comments->get(4),
            $comments->get(5),
            $comments->get(6),
        ]);

        $meta = [
            'page' => [
                'per-page' => 3,
                'from' => (string) $expected->first()->getRouteKey(),
                'to' => (string) $expected->last()->getRouteKey(),
                'has-more' => true,
            ],
        ];

        $response = $this
            ->actingAsUser()
            ->jsonApi('comments')
            ->page($page)
            ->get('/api/v1/comments');

        $response
            ->assertFetchedMany($expected)
            ->assertExactMeta($meta)
            ->assertExactLinks($this->createLinks(3, $expected->first(), $expected->last()));
    }

    public function testBeforeWithEqualDates()
    {
        /** @var Collection $equal */
        $equal = factory(Comment::class, 3)->create([
            'created_at' => Carbon::now()->subMinute(),
        ])->sortByDesc('id')->values();

        factory(Comment::class)->create([
            'created_at' => Carbon::now()->subMinutes(2),
        ]);

        $recent = factory(Comment::class)->create([
            'created_at' => Carbon::now()->subSecond(),
        ]);

        $expected = collect([
            $recent,
            $equal->first(),
            $equal->get(1)
        ]);

        $page = [
            'before' => $equal->last()->getRouteKey(),
        ];

        $meta = [
            'page' => [
                'per-page' => 15,
                'from' => (string) $expected->first()->getRouteKey(),
                'to' => (string) $expected->last()->getRouteKey(),
                'has-more' => true,
            ],
        ];

        $response = $this
            ->actingAsUser()
            ->jsonApi('comments')
            ->page($page)
            ->get('/api/v1/comments');

        $response
            ->assertFetchedMany($expected)
            ->assertExactMeta($meta);
    }

    /**
     * If the before key does not exist, we expect the cursor builder
     * to throw an exception which would constitute an internal server error.
     * Applications should validate the id before passing it to the cursor.
     */
    public function testBeforeDoesNotExist()
    {
        $response = $this
            ->actingAsUser()
            ->jsonApi('comments')
            ->page(['before' => '999'])
            ->get('/api/v1/comments');

        $response
            ->assertStatus(500);
    }

    public function testAfter()
    {
        /** @var Collection $comments */
        $comments = factory(Comment::class, 10)->create([
            'created_at' => function () {
                return $this->faker->dateTime();
            }
        ])->sortByDesc('created_at')->values();

        $page = [
            'limit' => '3',
            'after' => $comments->get(3)->getRouteKey(),
        ];

        $expected = collect([
            $comments->get(4),
            $comments->get(5),
            $comments->get(6),
        ]);

        $meta = [
            'page' => [
                'per-page' => 3,
                'from' => (string) $expected->first()->getRouteKey(),
                'to' => (string) $expected->last()->getRouteKey(),
                'has-more' => true,
            ],
        ];

        $response = $this
            ->actingAsUser()
            ->jsonApi('comments')
            ->page($page)
            ->get('/api/v1/comments');

        $response
            ->assertFetchedMany($expected)
            ->assertExactMeta($meta);
    }

    public function testAfterAscending()
    {
        $this->strategy->withAscending();

        /** @var Collection $comments */
        $comments = factory(Comment::class, 10)->create([
            'created_at' => function () {
                return $this->faker->dateTime();
            }
        ])->sortBy('created_at')->values();

        $page = [
            'limit' => '3',
            'after' => $comments->get(3)->getRouteKey(),
        ];

        $expected = collect([
            $comments->get(4),
            $comments->get(5),
            $comments->get(6),
        ]);

        $meta = [
            'page' => [
                'per-page' => 3,
                'from' => (string) $expected->first()->getRouteKey(),
                'to' => (string) $expected->last()->getRouteKey(),
                'has-more' => true,
            ],
        ];

        $response = $this
            ->actingAsUser()
            ->jsonApi('comments')
            ->page($page)
            ->get('/api/v1/comments');

        $response
            ->assertFetchedMany($expected)
            ->assertExactMeta($meta);
    }

    public function testAfterWithoutMore()
    {
        /** @var Collection $comments */
        $comments = factory(Comment::class, 4)->create([
            'created_at' => function () {
                return $this->faker->dateTime();
            }
        ])->sortByDesc('created_at')->values();

        $page = [
            'limit' => '10',
            'after' => $comments->get(1)->getRouteKey(),
        ];

        $expected = collect([
            $comments->get(2),
            $comments->get(3),
        ]);

        $meta = [
            'page' => [
                'per-page' => 10,
                'from' => (string) $expected->first()->getRouteKey(),
                'to' => (string) $expected->last()->getRouteKey(),
                'has-more' => false,
            ],
        ];

        $links = $this->createLinks(10, $expected->first(), $expected->last());
        unset($links['next']);

        $response = $this
            ->actingAsUser()
            ->jsonApi('comments')
            ->page($page)
            ->get('/api/v1/comments');

        $response
            ->assertFetchedMany($expected)
            ->assertExactMeta($meta)
            ->assertExactLinks($links);
    }

    public function testAfterWithEqualDates()
    {
        /** @var Collection $equal */
        $equal = factory(Comment::class, 3)->create([
            'created_at' => Carbon::now()->subMinute(),
        ])->sortByDesc('id')->values();

        $oldest = factory(Comment::class)->create([
            'created_at' => Carbon::now()->subMinutes(2),
        ]);

        factory(Comment::class)->create(['created_at' => Carbon::now()->subSecond()]);

        $expected = collect([
            $equal->get(1),
            $equal->last(),
            $oldest
        ]);

        $page = [
            'after' => $equal->first()->getRouteKey(),
        ];

        $meta = [
            'page' => [
                'per-page' => 15,
                'from' => (string) $expected->first()->getRouteKey(),
                'to' => (string) $expected->last()->getRouteKey(),
                'has-more' => false,
            ],
        ];

        $response = $this
            ->actingAsUser()
            ->jsonApi('comments')
            ->page($page)
            ->get('/api/v1/comments');

        $response
            ->assertFetchedMany($expected)
            ->assertExactMeta($meta);
    }

    /**
     * Test that we can override the before, after and limit page keys.
     */
    public function testAfterWithCustomKey()
    {
        $this->strategy
            ->withBeforeKey('ending-before')
            ->withAfterKey('starting-after')
            ->withLimitKey('per-page');

        /** @var Collection $comments */
        $comments = factory(Comment::class, 6)->create([
            'created_at' => function () {
                return $this->faker->dateTime();
            }
        ])->sortByDesc('created_at')->values();

        $page = [
            'per-page' => '3',
            'starting-after' => $comments->get(1)->getRouteKey(),
        ];

        $expected = collect([
            $comments->get(2),
            $comments->get(3),
            $comments->get(4),
        ]);

        $response = $this
            ->actingAsUser()
            ->jsonApi('comments')
            ->page($page)
            ->get('/api/v1/comments');

        $response
            ->assertFetchedMany($expected)
            ->assertJson([
                'meta' => [
                    'page' => [
                        'per-page' => 3,
                        'from' => $expected->first()->getRouteKey(),
                        'to' => $expected->last()->getRouteKey(),
                        'has-more' => true,
                    ],
                ],
                'links' => [
                    'first' => $this->buildLink(
                        '/api/v1/comments',
                        [
                            'page' => [
                                'per-page' => 3,
                            ],
                        ]
                    ),
                    'prev' => $this->buildLink(
                        '/api/v1/comments',
                        [
                            'page' => [
                                'ending-before' => $expected->first()->getRouteKey(),
                                'per-page' => 3,
                            ]
                        ]
                    ),
                    'next' => $this->buildLink(
                        '/api/v1/comments',
                        [
                            'page' => [
                                'starting-after' => $expected->last()->getRouteKey(),
                                'per-page' => 3,
                            ],
                        ]
                    ),
                ],
            ]);
    }

    /**
     * If the after key does not exist, we expect the cursor builder
     * to throw an exception which would constitute an internal server error.
     * Applications should validate the id before passing it to the cursor.
     */
    public function testAfterDoesNotExist()
    {
        $response = $this
            ->actingAsUser()
            ->jsonApi('comments')
            ->page(['after' => '999'])
            ->get('/api/v1/comments');

        $response
            ->assertStatus(500);
    }

    /**
     * If we supply both the before and after ids, only the before should be used.
     */
    public function testBeforeAndAfter()
    {
        /** @var Collection $comments */
        $comments = factory(Comment::class, 6)->create([
            'created_at' => function () {
                return $this->faker->dateTime();
            }
        ])->sortByDesc('created_at')->values();

        $page = [
            'limit' => '3',
            'before' => $comments->get(5)->getRouteKey(),
            'after' => $comments->get(1)->getRouteKey(),
        ];

        $expected = collect([
            $comments->get(2),
            $comments->get(3),
            $comments->get(4),
        ]);

        $meta = [
            'page' => [
                'per-page' => 3,
                'from' => (string) $expected->first()->getRouteKey(),
                'to' => (string) $expected->last()->getRouteKey(),
                'has-more' => true,
            ],
        ];

        $response = $this
            ->actingAsUser()
            ->jsonApi('comments')
            ->page($page)
            ->get('/api/v1/comments');

        $response
            ->assertFetchedMany($expected)
            ->assertExactMeta($meta)
            ->assertExactLinks($this->createLinks(3, $expected->first(), $expected->last()));
    }

    /**
     * Test use of the cursor paginator where the pagination column is
     * identical to the identifier column.
     */
    public function testSameColumnAndIdentifier()
    {
        $this->strategy->withColumn('id');

        /** @var Collection $comments */
        $comments = factory(Comment::class, 6)->create([
            'created_at' => function () {
                return $this->faker->dateTime();
            }
        ])->sortByDesc('id')->values();

        $page = [
            'limit' => '3',
            'before' => $comments->get(4)->getRouteKey(),
            'after' => $comments->get(1)->getRouteKey(),
        ];

        $expected = collect([
            $comments->get(1),
            $comments->get(2),
            $comments->get(3),
        ]);

        $meta = [
            'page' => [
                'per-page' => 3,
                'from' => (string) $expected->first()->getRouteKey(),
                'to' => (string) $expected->last()->getRouteKey(),
                'has-more' => true,
            ],
        ];

        $response = $this
            ->actingAsUser()
            ->jsonApi('comments')
            ->page($page)
            ->get('/api/v1/comments');

        $response
            ->assertFetchedMany($expected)
            ->assertExactMeta($meta)
            ->assertExactLinks($this->createLinks(3, $expected->first(), $expected->last()));
    }

    /**
     * Test that we can customise the meta nesting and underscore meta keys.
     */
    public function testCustomMeta()
    {
        $this->strategy->withMetaKey('cursor')->withUnderscoredMetaKeys();

        /** @var Collection $comments */
        $comments = factory(Comment::class, 6)->create([
            'created_at' => function () {
                return $this->faker->dateTime();
            }
        ])->sortByDesc('created_at')->values();

        $page = [
            'limit' => '3',
            'before' => $comments->get(4)->getRouteKey(),
        ];

        $expected = collect([
            $comments->get(1),
            $comments->get(2),
            $comments->get(3),
        ]);

        $meta = [
            'cursor' => [
                'per_page' => 3,
                'from' => (string) $expected->first()->getRouteKey(),
                'to' => (string) $expected->last()->getRouteKey(),
                'has_more' => true,
            ],
        ];

        $response = $this
            ->actingAsUser()
            ->jsonApi('comments')
            ->page($page)
            ->get('/api/v1/comments');

        $response
            ->assertFetchedMany($expected)
            ->assertExactMeta($meta);
    }

    /**
     * Test that we can change the column on which we paginate.
     */
    public function testColumn()
    {
        $this->strategy->withColumn('updated_at');

        /** @var Collection $comments */
        $comments = factory(Comment::class, 6)->create([
            'updated_at' => function () {
                return $this->faker->dateTime();
            }
        ])->sortByDesc('updated_at')->values();

        $page = [
            'limit' => '3',
            'before' => $comments->get(4)->getRouteKey(),
        ];

        $expected = collect([
            $comments->get(1),
            $comments->get(2),
            $comments->get(3),
        ]);

        $meta = [
            'page' => [
                'per-page' => 3,
                'from' => (string) $expected->first()->getRouteKey(),
                'to' => (string) $expected->last()->getRouteKey(),
                'has-more' => true,
            ],
        ];

        $response = $this
            ->actingAsUser()
            ->jsonApi('comments')
            ->page($page)
            ->get('/api/v1/comments');

        $response
            ->assertFetchedMany($expected)
            ->assertExactMeta($meta);
    }

    /**
     * @param int $limit
     * @param Comment $prev
     * @param Comment $next
     * @return array
     */
    private function createLinks($limit, $prev, $next)
    {
        return [
            'first' => $this->buildLink(
                '/api/v1/comments',
                [
                    'page' => [
                        'limit' => $limit,
                    ],
                ]
            ),
            'prev' => $this->buildLink(
                '/api/v1/comments',
                [
                    'page' => [
                        'before' => $prev->getRouteKey(),
                        'limit' => $limit,
                    ]
                ]
            ),
            'next' => $this->buildLink(
                '/api/v1/comments',
                [
                    'page' => [
                        'after' => $next->getRouteKey(),
                        'limit' => $limit,
                    ],
                ]
            ),
        ];
    }
}
