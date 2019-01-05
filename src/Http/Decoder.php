<?php

namespace CloudCreativity\LaravelJsonApi\Http;

use CloudCreativity\LaravelJsonApi\Exceptions\InvalidJsonException;
use Illuminate\Http\Request;
use function CloudCreativity\LaravelJsonApi\json_decode;

class Decoder
{

    /**
     * Decode a JSON API document from a request.
     *
     * @param Request $request
     * @return \stdClass|null
     *      the JSON API document, or null if the request is not providing JSON API content.
     * @throws InvalidJsonException
     */
    public function decode($request): ?\stdClass
    {
        return json_decode($request->getContent());
    }

    /**
     * Extract content data from a request.
     *
     * @param Request $request
     * @return array
     */
    public function extract($request): array
    {
        return $request->json()->all();
    }
}
