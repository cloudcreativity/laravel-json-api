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

namespace CloudCreativity\LaravelJsonApi\Http\Requests;

/**
 * Class FetchResource
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class FetchProcess extends ValidatedRequest
{

    /**
     * @return string
     */
    public function getProcessType(): string
    {
        return $this->jsonApiRequest->getProcessType();
    }

    /**
     * @return string
     */
    public function getProcessId(): string
    {
        return $this->jsonApiRequest->getProcessId();
    }

    /**
     * @inheritDoc
     */
    protected function authorize()
    {
        // @TODO

//        if (!$authorizer = $this->getAuthorizer()) {
//            return;
//        }
//
//        $authorizer->read($this->getRecord(), $this->request);
    }

    /**
     * @inheritDoc
     */
    protected function validateQuery()
    {
        // @TODO

//        if (!$validators = $this->getValidators()) {
//            return;
//        }
//
//        $this->passes(
//            $validators->fetchQuery($this->query())
//        );
    }

}
