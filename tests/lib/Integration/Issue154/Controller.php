<?php
/**
 * Copyright 2018 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Issue154;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use Illuminate\Http\Response;

class Controller extends JsonApiController
{

    /**
     * @var array
     */
    public $responses = [];

    /**
     * @var array
     */
    public $unexpected = [];

    /**
     * @return Response|null
     */
    public function saving()
    {
        return $this->response('saving');
    }

    /**
     * @return Response|null
     */
    public function creating()
    {
        return $this->response('creating');
    }

    /**
     * @return Response|null
     */
    public function updating()
    {
        return $this->response('updating');
    }

    /**
     * @return Response|null
     */
    public function saved()
    {
        return $this->response('saved');
    }

    /**
     * @return Response|null
     */
    public function created()
    {
        return $this->response('created');
    }

    /**
     * @return Response|null
     */
    public function updated()
    {
        return $this->response('updated');
    }

    /**
     * @return Response|null
     */
    public function deleting()
    {
        return $this->response('deleting');
    }

    /**
     * @return Response|null
     */
    public function deleted()
    {
        return $this->response('deleted');
    }

    /**
     * @param $name
     * @return Response|null
     */
    private function response($name)
    {
        if (in_array($name, $this->unexpected, true)) {
            throw new \RuntimeException("Not expecting {$name} to be invoked.");
        }

        return isset($this->responses[$name]) ? $this->responses[$name] : null;
    }
}
