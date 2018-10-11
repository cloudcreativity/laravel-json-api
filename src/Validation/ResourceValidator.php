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

namespace CloudCreativity\LaravelJsonApi\Validation;

use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;

class ResourceValidator extends AbstractValidator
{

    /**
     * @var ResourceObject
     */
    protected $resource;

    /**
     * ResourceValidator constructor.
     *
     * @param ValidatorContract $validator
     * @param ErrorTranslator $errors
     * @param ResourceObject $resource
     */
    public function __construct(
        ValidatorContract $validator,
        ErrorTranslator $errors,
        ResourceObject $resource
    ) {
        parent::__construct($validator, $errors);
        $this->resource = $resource;
    }

    /**
     * @inheritDoc
     */
    protected function createError(string $key, string $detail): ErrorInterface
    {
        return $this->errors->invalidResource(
            $this->resource->pointer($key),
            $detail
        );
    }

}
