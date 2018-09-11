<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Client\ClientInterface;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase as BaseTestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\parse_query;

abstract class TestCase extends BaseTestCase
{

    /**
     * @var HandlerStack
     */
    protected $handler;

    /**
     * @var MockHandler
     */
    protected $mock;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->handler = HandlerStack::create($this->mock = new MockHandler());
        $this->client = $this->api()->client('http://example.com', ['handler' => $this->handler]);
    }

    /**
     * @param $resource
     * @param int $status
     * @param array $headers
     * @return Response
     */
    protected function willSeeResource($resource, $status = 200, array $headers = [])
    {
        $json = $this->api()->encoder()->serializeData($resource);

        return $this->willSeeResponse($json, $status, $headers);
    }

    /**
     * @param array|null $json
     * @param int $status
     * @param array $headers
     * @return Response
     */
    protected function willSeeResponse(array $json = null, $status = 200, array $headers = [])
    {
        if ($json) {
            $headers['Content-Type'] = 'application/vnd.api+json';
            $body = json_encode($json);
        } else {
            $body = null;
        }

        $this->mock->append(
            $response = new Response($status, $headers, $body)
        );

        return $response;
    }

    /**
     * @param array $expected
     */
    protected function assertSentDocument(array $expected)
    {
        $this->assertJsonStringEqualsJsonString(
            json_encode($expected),
            (string) $this->mock->getLastRequest()->getBody()
        );
    }

    /**
     * @param $method
     * @param $path
     * @return void
     */
    protected function assertRequested($method, $path)
    {
        $uri = 'http://example.com/api/v1' . $path;
        $request = $this->mock->getLastRequest();
        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($uri, (string) $request->getUri(), 'request uri');
    }

    /**
     * @param $key
     * @param $expected
     * @return void
     */
    protected function assertHeader($key, $expected)
    {
        $request = $this->mock->getLastRequest();
        $actual = $request->getHeaderLine($key);
        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $expected
     * @return void
     */
    protected function assertQueryParameters(array $expected)
    {
        $query = $this->mock->getLastRequest()->getUri()->getQuery();
        $this->assertEquals($expected, parse_query($query));
    }
}
