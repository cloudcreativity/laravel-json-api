<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Validation;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{

    /**
     * @param $uri
     * @param $content
     * @param $method
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function doInvalidRequest($uri, $content, $method = 'POST')
    {
        if (!is_string($content)) {
            $content = json_encode($content);
        }

        $headers = [
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/vnd.api+json',
            'Accept' => 'application/vnd.api+json',
        ];

        return $this->call($method, $uri, [], [], [], $headers, $content);
    }
}
