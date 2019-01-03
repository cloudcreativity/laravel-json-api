<?php

/**
 * Copyright 2019 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Repositories;

use CloudCreativity\LaravelJsonApi\Contracts\Repositories\CodecMatcherRepositoryInterface;
use CloudCreativity\LaravelJsonApi\Repositories\CodecMatcherRepository;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Decoders\ArrayDecoder;
use Neomerx\JsonApi\Decoders\ObjectDecoder;
use Neomerx\JsonApi\Encoder\EncoderOptions;
use Neomerx\JsonApi\Factories\Factory;
use Neomerx\JsonApi\Http\Headers\AcceptHeader;
use Neomerx\JsonApi\Http\Headers\Header;

/**
 * Class CodecMatcherRepositoryTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class CodecMatcherRepositoryTest extends TestCase
{

    const A = 'application/vnd.api+json';
    const B = 'application/json';
    const C = 'text/plain';

    private $config = [
        'encoders' => [
            'application/vnd.api+json',
            'application/json' => JSON_BIGINT_AS_STRING,
            'text/plain' => [
                'options' => JSON_PRETTY_PRINT,
                'depth' => 123,
            ],
        ],
        'decoders' => [
            'application/vnd.api+json',
            'application/json' => ArrayDecoder::class,
        ],
    ];

    private $encoderA;
    private $encoderB;
    private $encoderC;

    private $decoderA;
    private $decoderB;

    /**
     * @var CodecMatcherRepositoryInterface
     */
    private $repository;

    protected function setUp()
    {
        $factory = new Factory();
        $urlPrefix = 'https://www.example.tld/api/v1';
        $schemas = $factory->createContainer(['Author' => 'AuthorSchema']);

        $this->encoderA = $factory->createEncoder($schemas, new EncoderOptions(0, $urlPrefix));
        $this->encoderB = $factory->createEncoder($schemas, new EncoderOptions(JSON_BIGINT_AS_STRING, $urlPrefix));
        $this->encoderC = $factory->createEncoder($schemas, new EncoderOptions(JSON_PRETTY_PRINT, $urlPrefix, 123));

        $this->decoderA = new ObjectDecoder();
        $this->decoderB = new ArrayDecoder();

        $this->repository = new CodecMatcherRepository($factory);
        $this->repository->registerSchemas($schemas)->registerUrlPrefix($urlPrefix);

        $this->repository->configure($this->config);
    }

    public function testCodecMatcher()
    {
        $codecMatcher = $this->repository->getCodecMatcher();

        $this->assertInstanceOf(CodecMatcherInterface::class, $codecMatcher);
    }

    /**
     * @depends testCodecMatcher
     */
    public function testEncoderA()
    {
        $codecMatcher = $this->repository->getCodecMatcher();
        $codecMatcher->matchEncoder(AcceptHeader::parse(static::A));

        $this->assertEquals($this->encoderA, $codecMatcher->getEncoder());
        $this->assertEquals(static::A, $codecMatcher->getEncoderHeaderMatchedType()->getMediaType());
        $this->assertEquals(static::A, $codecMatcher->getEncoderRegisteredMatchedType()->getMediaType());
    }

    /**
     * @depends testCodecMatcher
     */
    public function testEncoderB()
    {
        $codecMatcher = $this->repository->getCodecMatcher();
        $codecMatcher->matchEncoder(AcceptHeader::parse(static::B));

        $this->assertEquals($this->encoderB, $codecMatcher->getEncoder());
        $this->assertEquals(static::B, $codecMatcher->getEncoderHeaderMatchedType()->getMediaType());
        $this->assertEquals(static::B, $codecMatcher->getEncoderRegisteredMatchedType()->getMediaType());
    }

    /**
     * @depends testCodecMatcher
     */
    public function testEncoderC()
    {
        $codecMatcher = $this->repository->getCodecMatcher();
        $codecMatcher->matchEncoder(AcceptHeader::parse(static::C));

        $this->assertEquals($this->encoderC, $codecMatcher->getEncoder());
        $this->assertEquals(static::C, $codecMatcher->getEncoderHeaderMatchedType()->getMediaType());
        $this->assertEquals(static::C, $codecMatcher->getEncoderRegisteredMatchedType()->getMediaType());
    }

    /**
     * @depends testCodecMatcher
     */
    public function testDecoderA()
    {
        $codecMatcher = $this->repository->getCodecMatcher();
        $codecMatcher->matchDecoder(Header::parse(static::A, Header::HEADER_CONTENT_TYPE));

        $this->assertEquals($this->decoderA, $codecMatcher->getDecoder());
        $this->assertEquals(static::A, $codecMatcher->getDecoderHeaderMatchedType()->getMediaType());
        $this->assertEquals(static::A, $codecMatcher->getDecoderRegisteredMatchedType()->getMediaType());
    }

    /**
     * @depends testCodecMatcher
     */
    public function testDecoderB()
    {
        $codecMatcher = $this->repository->getCodecMatcher();
        $codecMatcher->matchDecoder(Header::parse(static::B, Header::HEADER_CONTENT_TYPE));

        $this->assertEquals($this->decoderB, $codecMatcher->getDecoder());
        $this->assertEquals(static::B, $codecMatcher->getDecoderHeaderMatchedType()->getMediaType());
        $this->assertEquals(static::B, $codecMatcher->getDecoderRegisteredMatchedType()->getMediaType());
    }

    /**
     * @depends testCodecMatcher
     */
    public function testDecoderC()
    {
        $codecMatcher = $this->repository->getCodecMatcher();
        $codecMatcher->matchDecoder(Header::parse(static::C, Header::HEADER_CONTENT_TYPE));

        $this->assertNull($codecMatcher->getDecoder());
        $this->assertNull($codecMatcher->getDecoderHeaderMatchedType());
        $this->assertNull($codecMatcher->getDecoderRegisteredMatchedType());
    }
}
