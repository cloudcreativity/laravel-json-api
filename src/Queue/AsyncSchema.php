<?php

namespace CloudCreativity\LaravelJsonApi\Queue;

use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;

trait AsyncSchema
{

    /**
     * @return string
     */
    public function getResourceType()
    {
        $api = property_exists($this, 'api') ? $this->api : null;

        return json_api($api)->getJobs()->getResource();
    }

    /**
     * @param AsynchronousProcess|null $resource
     * @return string
     */
    public function getSelfSubUrl($resource = null)
    {
        if (!$resource) {
            return '/' . $this->getResourceType();
        }

        return sprintf(
            '/%s/%s/%s',
            $resource->getResourceType(),
            $this->getResourceType(),
            $this->getId($resource)
        );
    }
}
