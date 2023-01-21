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

class EncoderOptions
{
    /**
     * @var int
     */
    private int $options;

    /**
     * @var int
     */
    private int $depth;

    /**
     * @var string|null
     */
    private ?string $urlPrefix;

    /**
     * EncoderOptions constructor.
     *
     * @param int $options
     * @param string|null $urlPrefix
     * @param int $depth
     */
    public function __construct(int $options = 0, string $urlPrefix = null, int $depth = 512)
    {
        $this->options   = $options;
        $this->depth     = $depth;
        $this->urlPrefix = $urlPrefix;
    }

    /**
     * @link http://php.net/manual/en/function.json-encode.php
     * @return int
     */
    public function getOptions(): int
    {
        return $this->options;
    }

    /**
     * @link http://php.net/manual/en/function.json-encode.php
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @return string|null
     */
    public function getUrlPrefix(): ?string
    {
        return $this->urlPrefix;
    }
}