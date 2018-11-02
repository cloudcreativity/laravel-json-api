<?php

namespace DummyApp\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use CloudCreativity\LaravelJsonApi\Http\Requests\ValidatedRequest;
use DummyApp\Avatar;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AvatarsController extends JsonApiController
{

    /**
     * @param Avatar $avatar
     * @param ValidatedRequest $request
     * @return StreamedResponse|null
     */
    protected function reading(Avatar $avatar, ValidatedRequest $request): ?StreamedResponse
    {
        if ($request->getCodec()->is($avatar->media_type)) {
            return Storage::disk('local')->download($avatar->path);
        }

        return null;
    }
}
