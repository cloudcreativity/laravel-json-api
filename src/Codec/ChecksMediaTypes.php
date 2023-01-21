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

namespace CloudCreativity\LaravelJsonApi\Codec;

/**
 * Trait ChecksMediaTypes
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait ChecksMediaTypes
{

    /**
     * Were any of the supplied media types decoded?
     *
     * @param string ...$mediaTypes
     * @return bool
     */
    public function didDecode(string ...$mediaTypes): bool
    {
        return app('json-api')
            ->currentRoute()
            ->getCodec()
            ->decodes(...$mediaTypes);
    }

    /**
     * Were none of the supplied media types decoded?
     *
     * @param string ...$mediaTypes
     * @return bool
     */
    public function didNotDecode(string ...$mediaTypes): bool
    {
        return !$this->didDecode(...$mediaTypes);
    }

    /**
     * Will any of the supplied media types be encoded?
     *
     * @param string ...$mediaTypes
     * @return bool
     */
    public function willEncode(string ...$mediaTypes): bool
    {
        return app('json-api')
            ->currentRoute()
            ->getCodec()
            ->encodes(...$mediaTypes);
    }

    /**
     * Will none of the supplied media types be encoded?
     *
     * @param string ...$mediaTypes
     * @return bool
     */
    public function willNotEncode(string ...$mediaTypes): bool
    {
        return !$this->willEncode(...$mediaTypes);
    }
}
