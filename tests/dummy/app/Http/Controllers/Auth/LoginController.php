<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

namespace DummyApp\Http\Controllers\Auth;

use CloudCreativity\LaravelJsonApi\Document\Error;
use CloudCreativity\LaravelJsonApi\Exceptions\ValidationException;
use CloudCreativity\LaravelJsonApi\Http\Controllers\CreatesResponses;
use CloudCreativity\LaravelJsonApi\Utils\Helpers;
use DummyApp\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers { sendFailedLoginResponse as protected originalSendFailedLoginResponse; }
    use CreatesResponses;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * @param Request $request
     * @param $user
     * @return Response|null
     */
    protected function authenticated(Request $request, $user)
    {
        if (Helpers::wantsJsonApi($request)) {
            return $this->reply()->content($user);
        }

        return null;
    }

    /**
     * Send a failed login response.
     *
     * The following is required to support Laravel 5.4 and 5.5.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @todo remove when dropping support for Laravel 5.4 and 5.5
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        if (Helpers::wantsJsonApi($request)) {
            throw new ValidationException(Error::create([
                'title' => 'Unprocessable Entity',
                'status' => '422',
                'detail' => trans('auth.failed'),
                'meta' => ['key' => 'email'],
            ]));
        }

        return $this->originalSendFailedLoginResponse($request);
    }
}
