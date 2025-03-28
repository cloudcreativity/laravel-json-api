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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\View;

use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Encoder\Encoder;
use CloudCreativity\LaravelJsonApi\Http\Query\QueryParameters;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use CloudCreativity\LaravelJsonApi\View\Renderer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class RendererTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class RendererTest extends TestCase
{

    /**
     * @var MockObject
     */
    private $service;

    /**
     * @var MockObject
     */
    private $api;

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        /** @var JsonApiService $service */
        $service = $this->service = $this->createMock(JsonApiService::class);
        $this->api = $this->createMock(Api::class);

        $this->renderer = new Renderer($service);
    }

    public function testCompileWith()
    {
        $expected = "<?php app('json-api.renderer')->with('foo'); ?>";

        $this->assertSame($expected, Renderer::compileWith("'foo'"));
    }

    public function testCompileWithUsingOptions()
    {
        $expected = "<?php app('json-api.renderer')->with('foo', JSON_PRETTY_PRINT, 250); ?>";

        $this->assertSame($expected, Renderer::compileWith("'foo', JSON_PRETTY_PRINT, 250"));
    }

    public function testCompileEncode()
    {
        $expected = "<?php echo app('json-api.renderer')->encode(\$data); ?>";

        $this->assertSame($expected, Renderer::compileEncode('$data'));
    }

    public function testCompileEncoderUsingOptions()
    {
        $expected = "<?php echo app('json-api.renderer')->encode(\$data, 'comments', ['author' => ['name']]); ?>";

        $this->assertSame($expected, Renderer::compileEncode("\$data, 'comments', ['author' => ['name']]"));
    }

    public function testEncodeWithDefaultApi()
    {
        $post = $this->withEncoder();
        $this->renderer->encode($post);
    }

    public function testEncodeWithNamedApi()
    {
        $post = $this->withEncoder('foo');
        $this->renderer->with('foo');
        $this->renderer->encode($post);
    }

    public function testEncodeWithOptions()
    {
        $post = $this->withEncoder('foo', JSON_PRETTY_PRINT, 250);
        $this->renderer->with('foo', JSON_PRETTY_PRINT, 250);
        $this->renderer->encode($post);
    }

    public function testEncodeWithParameters()
    {
        $params = new QueryParameters(['comments'], ['author' => ['name']]);
        $post = $this->withEncoder(null, 0, 512, $params);
        $this->renderer->encode($post, 'comments', ['author' => ['name']]);
    }

    /**
     * @param $name
     * @param int $options
     * @param int $depth
     * @param QueryParameters|null $parameters
     * @return object
     */
    private function withEncoder($name = null, $options = 0, $depth = 512, ?QueryParameters $parameters = null)
    {
        $post = (object) ['type' => 'posts', 'id' => '1'];

        $encoder = $this->createMock(Encoder::class);
        $encoder->expects($this->once())->method('withEncodingParameters')->with($parameters)->willReturnSelf();
        $encoder->expects($this->once())->method('encodeData')->with($post);

        $this->api->expects($this->once())->method('encoder')->with($options, $depth)->willReturn($encoder);

        $this->service->method('api')->with($name)->willReturn($this->api);

        return $post;
    }
}
