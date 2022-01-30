<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Validation;

use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\LaravelJsonApi;
use CloudCreativity\LaravelJsonApi\Rules\DateTimeIso8601;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\JsonApi\Posts\Validators;
use DummyApp\Post;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\MockObject\MockObject;

class FailedMetaTest extends TestCase
{

    /**
     * @var MockObject|Validators
     */
    private $validator;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        LaravelJsonApi::showValidatorFailures();

        $this->validator = $this
            ->getMockBuilder(Validators::class)
            ->setMethods(['rules'])
            ->setConstructorArgs([$this->app->make(Factory::class), json_api('v1')->getContainer()])
            ->getMock();

        $this->app->instance(Validators::class, $this->validator);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        LaravelJsonApi::$validationFailures = false;
    }

    /**
     * @return array
     */
    public function rulesProvider(): array
    {
        return [
            'before_or_equal' => [
                ['value' => '2019-01-01 00:00:00'],
                ['value' => 'before_or_equal:2018-12-31 23:59:59'],
                [
                    'status' => '422',
                    'title' => 'Unprocessable Entity',
                    'detail' => 'The value must be a date before or equal to 2018-12-31 23:59:59.',
                    'meta' => [
                        'failed' => [
                            'rule' => 'before-or-equal',
                            'options' => [
                                '2018-12-31 23:59:59',
                            ],
                        ],
                    ],
                    'source' => [
                        'pointer' => '/data/attributes/value',
                    ],
                ],
            ],
            'between' => [
                ['value' => 10],
                ['value' => 'numeric|between:1,9'],
                [
                    'status' => '422',
                    'title' => 'Unprocessable Entity',
                    'detail' => 'The value must be between 1 and 9.',
                    'meta' => [
                        'failed' => [
                            'rule' => 'between',
                            'options' => ['1', '9'],
                        ],
                    ],
                    'source' => [
                        'pointer' => '/data/attributes/value',
                    ],
                ],
            ],
            'exists' => [
                ['value' => 999999],
                ['value' => Rule::exists('posts', 'id')],
                [
                    'status' => '422',
                    'title' => 'Unprocessable Entity',
                    'detail' => 'The selected value is invalid.',
                    'meta' => [
                        'failed' => [
                            'rule' => 'exists',
                            // no options as they reveal database settings.
                        ],
                    ],
                    'source' => [
                        'pointer' => '/data/attributes/value',
                    ],
                ],
            ],
            'required' => [
                ['value' => null],
                ['value' => 'required'],
                [
                    'status' => '422',
                    'title' => 'Unprocessable Entity',
                    'detail' => 'The value field is required.',
                    'meta' => [
                        'failed' => [
                            'rule' => 'required',
                        ],
                    ],
                    'source' => [
                        'pointer' => '/data/attributes/value',
                    ],
                ],
            ],
            'rule object' => [
                ['value' => 'foobar'],
                ['value' => new DateTimeIso8601()],
                [
                    'status' => '422',
                    'title' => 'Unprocessable Entity',
                    'detail' => 'The value is not a valid ISO 8601 date and time.',
                    'meta' => [
                        'failed' => [
                            'rule' => 'date-time-iso8601',
                        ],
                    ],
                    'source' => [
                        'pointer' => '/data/attributes/value',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $attributes
     * @param array $rules
     * @param array $expected
     * @dataProvider rulesProvider
     */
    public function test(array $attributes, array $rules, array $expected): void
    {
        $data = [
            'type' => 'posts',
            'attributes' => $attributes,
        ];

        $this->validator->method('rules')->willReturn($rules);

        $response = $this
            ->jsonApi('posts')
            ->withData($data)
            ->post('/api/v1/posts');

        $response
            ->assertExactErrorStatus($expected);
    }

    /**
     * The unique rule is tested separately as we need to set up the database first.
     * As with other database rules, we expect the options to not be included in the
     * meta as they reveal database information.
     */
    public function testUnique(): void
    {
        $post = factory(Post::class)->create();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'value' => $post->slug,
            ],
        ];

        $expected = [
            'status' => '422',
            'title' => 'Unprocessable Entity',
            'detail' => 'The value has already been taken.',
            'meta' => [
                'failed' => [
                    'rule' => 'unique',
                    // no options as they reveal database settings.
                ],
            ],
            'source' => [
                'pointer' => '/data/attributes/value',
            ],
        ];

        $this->validator->method('rules')->willReturn([
            'value' => Rule::unique('posts', 'slug'),
        ]);

        $response = $this
            ->jsonApi('posts')
            ->withData($data)
            ->post('/api/v1/posts');

        $response
            ->assertExactErrorStatus($expected);
    }

    public function testMultiple(): void
    {
        $expected = [
            [
                'status' => '422',
                'title' => 'Unprocessable Entity',
                'detail' => 'The title must be a string.',
                'source' => [
                    'pointer' => '/data/attributes/title',
                ],
                'meta' => [
                    'failed' => [
                        'rule' => 'string',
                    ],
                ],
            ],
            [
                'status' => '422',
                'title' => 'Unprocessable Entity',
                'detail' => 'The title must be between 5 and 255 characters.',
                'source' => [
                    'pointer' => '/data/attributes/title',
                ],
                'meta' => [
                    'failed' => [
                        'rule' => 'between',
                        'options' => ['5', '255'],
                    ],
                ],
            ],
        ];

        $data = [
            'type' => 'posts',
            'attributes' => ['title' => 1],
        ];

        $this->validator->method('rules')->willReturn(['title' => 'string|between:5,255']);

        $response = $this
            ->jsonApi('posts')
            ->withData($data)
            ->post('/api/v1/posts');

        $response->assertExactErrors(422, $expected);
    }
}
