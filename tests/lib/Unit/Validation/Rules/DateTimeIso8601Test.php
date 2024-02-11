<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Validation\Rules;

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Rules\DateTimeIso8601;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;

class DateTimeIso8601Test extends TestCase
{

    /**
     * @var DateTimeIso8601
     */
    private $rule;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new DateTimeIso8601();
    }

    /**
     * @return array
     */
    public static function validProvider(): array
    {
        return [
            ['2018-01-01T12:00+00:00'],
            ['2018-01-01T12:00:00+00:00'],
            ['2018-01-01T12:00:00.1+01:00'],
            ['2018-01-01T12:00:00.12+02:00'],
            ['2018-01-01T12:00:00.123+03:00'],
            ['2018-01-01T12:00:00.1234+04:00'],
            ['2018-01-01T12:00:00.12345+05:00'],
            ['2018-01-01T12:00:00.123456+06:00'],
            ['2018-01-01T12:00Z'],
            ['2018-01-01T12:00:00Z'],
            ['2018-01-01T12:00:00.123Z'],
            ['2018-01-01T12:00:00.123456Z'],
        ];
    }

    /**
     * @return array
     */
    public static function invalidProvider(): array
    {
        return [
            [null],
            [false],
            [true],
            [[]],
            [new \stdClass()],
            [new \DateTime()],
            [new Carbon()],
            [time()],
            [''],
            ['2018'],
            ['2018-01'],
            ['2018-01-02'],
        ];
    }

    /**
     * @param $value
     * @dataProvider validProvider
     */
    public function testValid($value): void
    {
        $this->assertTrue($this->rule->passes('date', $value));
    }

    /**
     * @param $value
     * @dataProvider invalidProvider
     */
    public function testInvalid($value): void
    {
        $this->assertFalse($this->rule->passes('date', $value));
    }

    /**
     * @param string $value
     * @dataProvider validProvider
     */
    public function testValidValuesCanBeDates(string $value): void
    {
        $date = new \DateTime($value);

        $this->assertInstanceOf(\DateTime::class, $date);
    }
}
