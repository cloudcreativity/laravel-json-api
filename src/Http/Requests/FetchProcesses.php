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

namespace CloudCreativity\LaravelJsonApi\Http\Requests;

use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorProviderInterface;

/**
 * Class FetchResources
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class FetchProcesses extends ValidatedRequest
{

    /**
     * @return string
     */
    public function getProcessType(): string
    {
        return $this->jsonApiRequest->getProcessType();
    }

    /**
     * @inheritDoc
     */
    protected function authorize()
    {
        /**
         * If we can read the resource type that the processes belong to,
         * we can also read the processes. We therefore get the authorizer
         * for the resource type, not the process type.
         */
        if (!$authorizer = $this->getAuthorizer()) {
            return;
        }

        $authorizer->index($this->getType(), $this->request);
    }

    /**
     * @inheritDoc
     */
    protected function validateQuery()
    {
        if (!$validators = $this->getValidators()) {
            return;
        }

        /** Pre-1.0 validators */
        if ($validators instanceof ValidatorProviderInterface) {
            $validators->searchQueryChecker()->checkQuery($this->getEncodingParameters());
            return;
        }

        /** 1.0 validators */
        $this->passes(
            $validators->fetchManyQuery($this->query())
        );
    }

    /**
     * @inheritdoc
     */
    protected function getValidators()
    {
        return $this->container->getValidatorsByResourceType(
            $this->getProcessType()
        );
    }

}
