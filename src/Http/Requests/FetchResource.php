<?php

namespace CloudCreativity\LaravelJsonApi\Http\Requests;

/**
 * Class FetchResource
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class FetchResource extends ValidatedRequest
{

    /**
     * @inheritDoc
     */
    protected function authorize()
    {
        if (!$authorizer = $this->getAuthorizer()) {
            return;
        }

        $authorizer->read($this->getRecord(), $this->request);
    }

    /**
     * @inheritDoc
     */
    protected function validateQuery()
    {
        if (!$validators = $this->getValidators()) {
            return;
        }

        $validators->resourceQueryChecker()->checkQuery($this->getParameters());
    }

    /**
     * @inheritDoc
     */
    protected function validateDocument()
    {
        // no-op
    }

}
