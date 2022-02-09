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

use Illuminate\Support\Arr;

final class Config
{

    /**
     * @var array
     */
    private $config;

    /**
     * Config constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get all config.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Get the database connection for controller transactions.
     *
     * @return string|null
     * @deprecated
     */
    public function dbConnection(): ?string
    {
        return Arr::get($this->config, 'controllers.connection');
    }

    /**
     * Should database transactions be used by controllers?
     *
     * @return bool
     */
    public function dbTransactions(): bool
    {
        return Arr::get($this->config, 'controllers.transactions', true);
    }

    /**
     * Get the decoding media types configuration.
     *
     * @return array
     */
    public function decoding(): array
    {
        return $this->config['decoding'];
    }

    /**
     * Get the encoding media types configuration.
     *
     * @return array
     */
    public function encoding(): array
    {
        return $this->config['encoding'] ?? [];
    }

    /**
     * Get the asynchronous job configuration.
     *
     * @return array
     */
    public function jobs(): array
    {
        return $this->config['jobs'] ?? [];
    }

    /**
     * Get the default namespace for the application's models.
     *
     * @return string|null
     */
    public function modelNamespace(): ?string
    {
        return $this->config['model-namespace'] ?? null;
    }

    /**
     * Get resource providers.
     *
     * @return array
     */
    public function providers(): array
    {
        return $this->config['providers'] ?? [];
    }

    /**
     * Get the supported extensions.
     *
     * @return string|null
     * @deprecated
     */
    public function supportedExt(): ?string
    {
        return $this->config['supported-ext'] ?? null;
    }

    /**
     * @return array
     */
    public function url(): array
    {
        return $this->config['url'] ?? [];
    }

    /**
     * Are the application's models predominantly Eloquent models?
     *
     * @return bool
     */
    public function useEloquent(): bool
    {
        return $this->config['use-eloquent'] ?? true;
    }
}
