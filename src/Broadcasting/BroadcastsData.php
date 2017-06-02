<?php

namespace CloudCreativity\LaravelJsonApi\Broadcasting;

use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use CloudCreativity\JsonApi\Encoder\Encoder;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;

trait BroadcastsData
{

    /**
     * @return string
     */
    protected function broadcastApi()
    {
        if (property_exists($this, 'broadcastApi')) {
            return $this->broadcastApi;
        }

        return 'default';
    }

    /**
     * @return string|null
     */
    protected function broadcastApiHost()
    {
        if (property_exists($this, 'broadcastApiHost')) {
            return $this->broadcastApiHost;
        }

        return null;
    }

    /**
     * @return Encoder
     */
    protected function broadcastEncoder()
    {
        /** @var JsonApiService $service */
        $service = app(JsonApiService::class);

        return $service->encoder($this->broadcastApi(), $this->broadcastApiHost());
    }

    /**
     * @param $data
     * @param string|string[]|null $includePaths
     * @param array|null $fieldsets
     * @return array
     */
    protected function serializeData($data, $includePaths = null, array $fieldsets = null)
    {
        $params = new EncodingParameters($includePaths ? (array) $includePaths : null, $fieldsets);

        return $this->broadcastEncoder()->serializeData($data, $params);
    }
}
