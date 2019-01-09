<?php

namespace DummyApp\JsonApi\Avatars;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use DummyApp\Avatar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class Adapter extends AbstractAdapter
{

    /**
     * Adapter constructor.
     */
    public function __construct()
    {
        parent::__construct(new Avatar());
    }

    /**
     * @inheritdoc
     */
    public function create(array $document, EncodingParametersInterface $parameters)
    {
        $path = request()->file('avatar')->store('avatars');

        $data = [
            'type' => 'avatars',
            'attributes' => [
                'path' => $path,
                'media-type' => Storage::disk('local')->mimeType($path)
            ],
        ];

        return parent::create(compact('data'), $parameters);
    }

    /**
     * @param Avatar $avatar
     * @return void
     */
    protected function creating(Avatar $avatar): void
    {
        $avatar->user()->associate(Auth::user());
    }

    /**
     * @inheritDoc
     */
    protected function filter($query, Collection $filters)
    {
        // TODO: Implement filter() method.
    }


}
