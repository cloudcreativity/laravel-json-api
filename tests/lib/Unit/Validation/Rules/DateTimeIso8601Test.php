<?php

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
    protected function setUp()
    {
        parent::setUp();
        $this->rule = new DateTimeIso8601();
    }

    /**
     * @return array
     */
    public function validProvider(): array
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
    public function invalidProvider(): array
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
