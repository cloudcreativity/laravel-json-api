<?php

namespace CloudCreativity\LaravelJsonApi\Http\Requests;

/**
 * Class FetchResources
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class FetchResources extends ValidatedRequest
{

    /**
     * @inheritDoc
     */
    protected function authorize()
    {
        if (!$authorizer = $this->getAuthorizer()) {
            return;
        }

        $authorizer->index($this->getResourceType(), $this->request);
    }

    /**
     * @inheritDoc
     */
    protected function validateQuery()
    {
        if (!$validators = $this->getValidators()) {
            return;
        }

        $validators->searchQueryChecker()->checkQuery($this->getParameters());
    }

    /**
     * @inheritDoc
     */
    protected function validateDocument()
    {
        // no-op
    }

}
