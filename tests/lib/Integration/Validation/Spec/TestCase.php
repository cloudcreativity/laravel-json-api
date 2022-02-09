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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Validation\Spec;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase as BaseTestCase;
use LaravelJsonApi\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{

    /**
     * @param $uri
     * @param $content
     * @param $method
     * @return TestResponse
     */
    protected function doInvalidRequest($uri, $content, $method = 'POST'): TestResponse
    {
        if (!is_string($content)) {
            $content = json_encode($content);
        }

        $headers = $this->transformHeadersToServerVars([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/vnd.api+json',
            'Accept' => 'application/vnd.api+json',
        ]);

        return TestResponse::cast(
            $this->call($method, $uri, [], [], [], $headers, $content)
        );
    }
}
