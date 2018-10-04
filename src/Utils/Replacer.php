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

namespace CloudCreativity\LaravelJsonApi\Utils;

use CloudCreativity\LaravelJsonApi\Contracts\Utils\ReplacerInterface;
use InvalidArgumentException;

/**
 * Class Replacer
 *
 * @package CloudCreativity\LaravelJsonApi
 * @deprecated 2.0.0
 */
class Replacer implements ReplacerInterface
{

    /**
     * @param string $message
     * @param array $values
     * @return string
     */
    public function replace($message, array $values)
    {
        if (!is_string($message)) {
            throw new InvalidArgumentException('Expecting message to be a string.');
        }

        foreach ($values as $key => $value) {
            $message = str_replace(
                $this->parseKey($key),
                $this->parseValue($value),
                $message
            );
        }

        return $message;
    }

    /**
     * @param $key
     * @return string
     */
    protected function parseKey($key)
    {
        return sprintf('{%s}', $key);
    }

    /**
     * @param $value
     * @return string
     */
    protected function parseValue($value)
    {
        if (is_object($value)) {
            return '<object>';
        } elseif (is_null($value)) {
            return 'null';
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_scalar($value)) {
            return (string) $value;
        }

        $ret = [];

        foreach ((array) $value as $v) {
            $ret[] = $this->parseValue($v);
        }

        return implode(', ', $ret);
    }
}
