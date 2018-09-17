<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

use CloudCreativity\LaravelJsonApi\Exceptions\ClientException;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;
use Neomerx\JsonApi\Encoder\Parameters\SortParameter;

class ListAllTest extends TestCase
{


    public function test()
    {
        $expected = $this->willSeeResponse(['data' => []]);
        $response = $this->client->query('posts');

        $this->assertSame($expected, $response);
        $this->assertRequested('GET', '/posts');
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testWithParameters()
    {
        $this->willSeeResponse(['data' => []]);
        $this->client->query('posts', [
            'include' => 'author,site',
            'sort' => '-created-at,author',
            'filter' => ['author' => '99'],
            'page' => ['number' => '1', 'size' => '15'],
        ]);

        $this->assertQueryParameters([
            'include' => 'author,site',
            'sort' => '-created-at,author',
            'page[number]' => '1',
            'page[size]' => '15',
            'filter[author]' => '99',
        ]);
    }

    public function testWithEncodingParameters()
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
        $this->client->query('posts', $parameters);

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
        $options = [
            'headers' => [
                'X-Foo' => 'Bar'
            ],
        ];

        $this->willSeeResponse(['data' => []]);
        $this->client->withOptions($options)->query('posts');

        $this->assertHeader('X-Foo', 'Bar');
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testError()
    {
        $this->willSeeErrors([], 405);
        $this->expectException(ClientException::class);
        $this->client->query('posts');
    }
}
