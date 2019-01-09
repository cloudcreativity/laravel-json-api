<?php

namespace DummyApp\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use DummyApp\Avatar;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AvatarsController extends JsonApiController
{

    /**
     * @param Avatar $avatar
     * @return StreamedResponse|null
     */
    protected function reading(Avatar $avatar): ?StreamedResponse
    {
        if ($this->willNotEncode($avatar->media_type)) {
            return null;
        }

        abort_unless(
            Storage::disk('local')->exists($avatar->path),
            404,
            'The image file does not exist.'
        );

        return Storage::disk('local')->download($avatar->path);
    }
}
