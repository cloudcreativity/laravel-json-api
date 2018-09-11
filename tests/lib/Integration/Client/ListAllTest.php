<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;
use Neomerx\JsonApi\Encoder\Parameters\SortParameter;
use Neomerx\JsonApi\Exceptions\JsonApiException;

class ListAllTest extends TestCase
{


    public function test()
    {
        $expected = $this->willSeeResponse(['data' => []]);
        $response = $this->client->index('posts');

        $this->assertSame($expected, $response->getPsrResponse());
        $this->assertRequested('GET', '/posts');
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testWithParameters()
    {
        $parameters = new EncodingParameters(
            ['author', 'site'],
            ['author' => ['first-name', 'surname'], 'site' => ['uri']],
            [new SortParameter('created-at', false), new SortParameter('author', true)],
            ['number' => 1, 'size' => 15],
            ['author' => 99],
            ['foo' => 'bar']
        );

        $this->willSeeResponse(['data' => []]);
        $this->client->index('posts', $parameters);

        $this->assertQueryParameters([
            'include' => 'author,site',
            'fields[author]' => 'first-name,surname',
            'fields[site]' => 'uri',
            'sort' => '-created-at,author',
            'page[number]' => '1',
            'page[size]' => '15',
            'filter[author]' => '99',
            'foo' => 'bar'
        ]);
    }

    public function testWithOptions()
    {
        $this->willSeeResponse(['data' => []]);

        $this->client->index('posts', null, [
            'headers' => [
                'X-Foo' => 'Bar'
            ],
        ]);

        $this->assertHeader('X-Foo', 'Bar');
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testError()
    {
        $this->willSeeErrors([], 405);
        $this->expectException(JsonApiException::class);
        $this->client->index('posts');
    }
}
