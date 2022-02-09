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

namespace CloudCreativity\LaravelJsonApi\Contracts\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

/**
 * Interface AuthorizerInterface
 *
 * @package CloudCreativity\LaravelJsonApi
 */
interface AuthorizerInterface
{

    /**
     * Authorize a resource index request.
     *
     * @param string $type
     *      the domain record type.
     * @param Request $request
     *      the inbound request.
     * @return void
     * @throws AuthenticationException|AuthorizationException
     *      if the request is not authorized.
     */
    public function index($type, $request);

    /**
     * Authorize a resource create request.
     *
     * @param string $type
     *      the domain record type.
     * @param Request $request
     *      the inbound request.
     * @return void
     * @throws AuthenticationException|AuthorizationException
     *      if the request is not authorized.
     */
    public function create($type, $request);

    /**
     * Authorize a resource read request.
     *
     * @param object $record
     *      the domain record.
     * @param Request $request
     *      the inbound request.
     * @return void
     * @throws AuthenticationException|AuthorizationException
     *      if the request is not authorized.
     */
    public function read($record, $request);

    /**
     * Authorize a resource update request.
     *
     * @param object $record
     *      the domain record.
     * @param Request $request
     *      the inbound request.
     * @return void
     * @throws AuthenticationException|AuthorizationException
     *      if the request is not authorized.
     */
    public function update($record, $request);

    /**
     * Authorize a resource read request.
     *
     * @param object $record
     *      the domain record.
     * @param Request $request
     *      the inbound request.
     * @return void
     * @throws AuthenticationException|AuthorizationException
     *      if the request is not authorized.
     */
    public function delete($record, $request);

    /**
     * Authorize a read relationship request.
     *
     * This is used to authorize GET requests to relationship endpoints, i.e.:
     *
     * ```
     * GET /api/posts/1/comments
     * GET /api/posts/1/relationships/comments
     * ```
     *
     * `$record` will be the post domain record (object) and `$field` will be the string `comments`.
     *
     * @param object $record
     *      the domain record.
     * @param string $field
     *      the JSON API field name for the relationship.
     * @param Request $request
     *      the inbound request.
     * @return void
     * @throws AuthenticationException|AuthorizationException
     *      if the request is not authorized.
     */
    public function readRelationship($record, $field, $request);

    /**
     * Authorize a modify relationship request.
     *
     * This is used to authorize `POST`, `PATCH` and `DELETE` requests to relationship endpoints, i.e.:
     *
     * ```
     * POST /api/posts/1/relationships/comments
     * PATH /api/posts/1/relationships/comments
     * DELETE /api/posts/1/relationships/comments
     * ```
     *
     * `$record` will be the post domain record (object) and `$field` will be the string `comments`.
     *
     * @param object $record
     *      the domain record.
     * @param string $field
     *      the JSON API field name for the relationship.
     * @param Request $request
     *      the inbound request.
     * @return void
     * @throws AuthenticationException|AuthorizationException
     *      if the request is not authorized.
     */
    public function modifyRelationship($record, $field, $request);

}
