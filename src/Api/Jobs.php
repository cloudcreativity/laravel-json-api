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

namespace CloudCreativity\LaravelJsonApi\Api;

use CloudCreativity\LaravelJsonApi\Queue\ClientJob;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;

class Jobs
{

    /**
     * @var string
     */
    private $resource;

    /**
     * @var string
     */
    private $model;

    /**
     * @param array $input
     * @return Jobs
     */
    public static function fromArray(array $input): self
    {
        return new self(
            $input['resource'] ?? ResourceRegistrar::KEYWORD_PROCESSES,
            $input['model'] ?? ClientJob::class
        );
    }

    /**
     * Jobs constructor.
     *
     * @param string $resource
     * @param string $model
     */
    public function __construct(string $resource, string $model)
    {
        if (!class_exists($model)) {
            throw new \InvalidArgumentException("Expecting {$model} to be a valid class name.");
        }

        $this->resource = $resource;
        $this->model = $model;
    }

    /**
     * @return string
     */
    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

}
