<?php

namespace DummyApp\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Http\Controllers\CreatesResponses;
use Illuminate\Http\Response;

class HomeController extends Controller
{

    use CreatesResponses;

    /**
     * @return Response
     */
    public function index(): Response
    {
        return $this->reply()->meta([
            'version' => json_api()->getName(),
        ]);
    }
}
