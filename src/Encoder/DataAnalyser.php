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

declare(strict_types=1);

namespace CloudCreativity\LaravelJsonApi\Encoder;

use ArrayAccess;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use Generator;
use Illuminate\Support\Enumerable;
use Iterator;
use RuntimeException;

class DataAnalyser
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * DataAnalyser constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param object|iterable|null $data
     * @return object|null
     */
    public function getRootObject($data): ?object
    {
        if ($data instanceof Generator) {
            throw new RuntimeException('Generators are not supported as resource collections.');
        }

        if (null === $data || $this->isResource($data)) {
            return $data;
        }

        $value = $this->getRootObjectFromIterable($data);

        if (null === $value || $this->isResource($value)) {
            return $value;
        }

        throw new RuntimeException(
            sprintf('Unexpected data type: %s.', get_debug_type($value)),
        );
    }

    /**
     * @param object|iterable|null $data
     * @return array
     */
    public function getIncludePaths($data): array
    {
        $includePaths = [];
        $root = $this->getRootObject($data);

        if (null !== $root) {
            $includePaths = $this->container->getSchema($root)->getIncludePaths();
        }

        return $includePaths;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private function isResource($value): bool
    {
        return is_object($value) && $this->container->hasSchema($value);
    }

    /**
     * @param iterable $data
     * @return object|null
     */
    private function getRootObjectFromIterable(iterable $data): ?object
    {
        if (is_array($data)) {
            return $data[0] ?? null;
        }

        if ($data instanceof Enumerable) {
            return $data->first();
        }

        if ($data instanceof Iterator) {
            $data->rewind();
            return $data->valid() ? $data->current() : null;
        }

        foreach ($data as $value) {
            return $value;
        }

        return null;
    }
}