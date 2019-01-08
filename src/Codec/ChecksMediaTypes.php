<?php

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
