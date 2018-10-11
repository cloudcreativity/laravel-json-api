<?php
/**
 * Copyright 2018 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Validation;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Document\Error;

/**
 * Class ErrorTranslator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ErrorTranslator
{

    /**
     * @var Translator
     */
    private $translator;

    /**
     * ErrorTranslator constructor.
     *
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Create an error for a member that is required.
     *
     * @param string $path
     * @param string $member
     * @return ErrorInterface
     */
    public function memberRequired(string $path, string $member): ErrorInterface
    {
        return new Error(
            null,
            null,
            Response::HTTP_BAD_REQUEST,
            $this->trans('member_required', 'code'),
            $this->trans('member_required', 'title'),
            $this->trans('member_required', 'detail', compact('member')),
            $this->pointer($path)
        );
    }

    /**
     * Create an error for a member that must be an object.
     *
     * @param string $path
     * @param string $member
     * @return ErrorInterface
     */
    public function memberNotObject(string $path, string $member): ErrorInterface
    {
        return new Error(
            null,
            null,
            Response::HTTP_BAD_REQUEST,
            $this->trans('member_object_expected', 'code'),
            $this->trans('member_object_expected', 'title'),
            $this->trans('member_object_expected', 'detail', compact('member')),
            $this->pointer($path, $member)
        );
    }

    /**
     * Create an error for a member that must be a string.
     *
     * @param string $path
     * @param string $member
     * @return ErrorInterface
     */
    public function memberNotString(string $path, string $member): ErrorInterface
    {
        return new Error(
            null,
            null,
            Response::HTTP_BAD_REQUEST,
            $this->trans('member_string_expected', 'code'),
            $this->trans('member_string_expected', 'title'),
            $this->trans('member_string_expected', 'detail', compact('member')),
            $this->pointer($path, $member)
        );
    }

    /**
     * Create an error for a member that cannot be an empty value.
     *
     * @param string $path
     * @param string $member
     * @return ErrorInterface
     */
    public function memberEmpty(string $path, string $member): ErrorInterface
    {
        return new Error(
            null,
            null,
            Response::HTTP_BAD_REQUEST,
            $this->trans('member_empty', 'code'),
            $this->trans('member_empty', 'title'),
            $this->trans('member_empty', 'detail', compact('member')),
            $this->pointer($path, $member)
        );
    }

    /**
     * Create an error for when the resource type is not supported by the endpoint.
     *
     * @param string $type
     *      the resource type that is not supported.
     * @param string $path
     * @return ErrorInterface
     */
    public function resourceTypeNotSupported(string $type, string $path = '/data'): ErrorInterface
    {
        return new Error(
            null,
            null,
            Response::HTTP_CONFLICT,
            $this->trans('resource_type_not_supported', 'code'),
            $this->trans('resource_type_not_supported', 'title'),
            $this->trans('resource_type_not_supported', 'detail', compact('type')),
            $this->pointer($path, 'type')
        );
    }

    /**
     * Create an error for when a resource type is not recognised.
     *
     * @param string $type
     *      the resource type that is not recognised.
     * @param string $path
     * @return ErrorInterface
     */
    public function resourceTypeNotRecognised(string $type, string $path = '/data'): ErrorInterface
    {
        return new Error(
            null,
            null,
            Response::HTTP_BAD_REQUEST,
            $this->trans('resource_type_not_recognised', 'code'),
            $this->trans('resource_type_not_recognised', 'title'),
            $this->trans('resource_type_not_recognised', 'detail', compact('type')),
            $this->pointer($path, 'type')
        );
    }

    /**
     * Create an error for when the resource id is not supported by the endpoint.
     *
     * @param string $id
     *      the resource id that is not supported.
     * @param string $path
     * @return ErrorInterface
     */
    public function resourceIdNotSupported(string $id, string $path = '/data'): ErrorInterface
    {
        return new Error(
            null,
            null,
            Response::HTTP_CONFLICT,
            $this->trans('resource_id_not_supported', 'code'),
            $this->trans('resource_id_not_supported', 'title'),
            $this->trans('resource_id_not_supported', 'detail', compact('id')),
            $this->pointer($path, 'id')
        );
    }

    /**
     * Create an error for a resource already existing.
     *
     * @param string $type
     *      the resource type
     * @param string $id
     *      the resource id
     * @param string $path
     * @return ErrorInterface
     */
    public function resourceExists(string $type, string $id, string $path = '/data'): ErrorInterface
    {
        return new Error(
            null,
            null,
            Response::HTTP_CONFLICT,
            $this->trans('resource_exists', 'code'),
            $this->trans('resource_exists', 'title'),
            $this->trans('resource_exists', 'detail', compact('type', 'id')),
            $this->pointer($path)
        );
    }

    /**
     * Create an error for a resource identifier that does not exist.
     *
     * @param string $path
     * @return ErrorInterface
     */
    public function resourceDoesNotExist(string $path): ErrorInterface
    {
        return new Error(
            null,
            null,
            Response::HTTP_NOT_FOUND,
            $this->trans('resource_not_found', 'code'),
            $this->trans('resource_not_found', 'title'),
            $this->trans('resource_not_found', 'detail'),
            $this->pointer($path)
        );
    }

    /**
     * Create an error for an invalid resource.
     *
     * @param string $path
     * @param string|null $detail
     *      the validation message (already translated).
     * @return ErrorInterface
     */
    public function invalidResource(string $path, ?string $detail = null): ErrorInterface
    {
        return new Error(
            null,
            null,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $this->trans('resource_invalid', 'code'),
            $this->trans('resource_invalid', 'title'),
            $detail ?: $this->trans('resource_invalid', 'detail'),
            $this->pointer($path)
        );
    }

    /**
     * Create an error for an invalid query parameter.
     *
     * @param string $param
     * @param string|null $detail
     *      the validation message (already translated).
     * @return ErrorInterface
     */
    public function invalidQueryParameter(string $param, ?string $detail = null): ErrorInterface
    {
        return new Error(
            null,
            null,
            Response::HTTP_BAD_REQUEST,
            $this->trans('query_invalid', 'code'),
            $this->trans('query_invalid', 'title'),
            $detail ?: $this->trans('query_invalid', 'detail'),
            [Error::SOURCE_PARAMETER => $param]
        );
    }

    /**
     * Translate an error member value.
     *
     * @param string $key
     *      the key for the JSON API error object.
     * @param string $member
     *      the JSON API error object member name.
     * @param array $replace
     * @param string|null $locale
     * @return string|null
     */
    protected function trans(string $key, string $member, array $replace = [], ?string $locale = null)
    {
        return $this->translator->trans(
            "jsonapi::errors.{$key}.{$member}",
            $replace,
            $locale
        ) ?: null;
    }

    /**
     * Create a source pointer for the specified path and optional member at that path.
     *
     * @param string $path
     * @param string|null $member
     * @return array
     */
    protected function pointer(string $path, ?string $member = null): array
    {
        if (!$member) {
            $pointer = $path;
        } else {
            $path = rtrim($path, '/');
            $pointer = $member ? sprintf('%s/%s', $path, $member) : $path;
        }

        return [Error::SOURCE_POINTER => $pointer];
    }
}
