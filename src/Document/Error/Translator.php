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

namespace CloudCreativity\LaravelJsonApi\Document\Error;

use CloudCreativity\LaravelJsonApi\Exceptions\ValidationException;
use CloudCreativity\LaravelJsonApi\LaravelJsonApi;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Contracts\Translation\Translator as IlluminateTranslator;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Schema\ErrorInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Neomerx\JsonApi\Schema\Error as NeomerxError;
use Neomerx\JsonApi\Schema\ErrorCollection;

/**
 * Class Translator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Translator
{

    /**
     * @var IlluminateTranslator
     */
    protected $translator;

    /**
     * Is failed meta included in generated error objects?
     *
     * @var bool
     */
    private $includeFailed;

    /**
     * Translator constructor.
     *
     * @param IlluminateTranslator $translator
     */
    public function __construct(IlluminateTranslator $translator)
    {
        $this->translator = $translator;
        $this->includeFailed = LaravelJsonApi::$validationFailures;
    }

    /**
     * Create an error for when a request is not authenticated.
     *
     * @return ErrorInterface
     */
    public function authentication(): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            null,
            Response::HTTP_UNAUTHORIZED,
            $this->trans('unauthorized', 'code'),
            $this->trans('unauthorized', 'title'),
            $this->trans('unauthorized', 'detail')
        );
    }

    /**
     * Create an error for when a request is not authorized.
     *
     * @return ErrorInterface
     */
    public function authorization(): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            null,
            Response::HTTP_FORBIDDEN,
            $this->trans('forbidden', 'code'),
            $this->trans('forbidden', 'title'),
            $this->trans('forbidden', 'detail')
        );
    }

    /**
     * Create an error for a token mismatch.
     *
     * @return ErrorInterface
     */
    public function tokenMismatch(): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            null,
            419,
            $this->trans('token_mismatch', 'code'),
            $this->trans('token_mismatch', 'title'),
            $this->trans('token_mismatch', 'detail')
        );
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
        return new NeomerxError(
            null,
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
        return new NeomerxError(
            null,
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
     * Create an error for a member that must be a resource identifier.
     *
     * @param string $path
     * @param string $member
     * @return ErrorInterface
     */
    public function memberNotIdentifier(string $path, string $member): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            null,
            Response::HTTP_BAD_REQUEST,
            $this->trans('member_identifier_expected', 'code'),
            $this->trans('member_identifier_expected', 'title'),
            $this->trans('member_identifier_expected', 'detail', compact('member')),
            $this->pointer($path, $member)
        );
    }

    /**
     * Create an error for when a member has a field that is not allowed.
     *
     * @param string $path
     * @param string $member
     * @param string $field
     * @return ErrorInterface
     */
    public function memberFieldNotAllowed(string $path, string $member, string $field): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            null,
            Response::HTTP_BAD_REQUEST,
            $this->trans('member_field_not_allowed', 'code'),
            $this->trans('member_field_not_allowed', 'title'),
            $this->trans('member_field_not_allowed', 'detail', compact('member', 'field')),
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
        return new NeomerxError(
            null,
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
        return new NeomerxError(
            null,
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
        return new NeomerxError(
            null,
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
        return new NeomerxError(
            null,
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
        return new NeomerxError(
            null,
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
     * Create an error for when a resource does not support client-generated ids.
     *
     * @param string $type
     * @param string $path
     * @return ErrorInterface
     */
    public function resourceDoesNotSupportClientIds(string $type, string $path = '/data'): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            null,
            Response::HTTP_FORBIDDEN,
            $this->trans('resource_client_ids_not_supported', 'code'),
            $this->trans('resource_client_ids_not_supported', 'title'),
            $this->trans('resource_client_ids_not_supported', 'detail', compact('type')),
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
        return new NeomerxError(
            null,
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
        return new NeomerxError(
            null,
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
     * Create an error for when a resource field exists in both the attributes and relationships members.
     *
     * @param string $field
     * @param string $path
     * @return ErrorInterface
     */
    public function resourceFieldExistsInAttributesAndRelationships(
        string $field,
        string $path = '/data'
    ): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            null,
            Response::HTTP_BAD_REQUEST,
            $this->trans('resource_field_exists_in_attributes_and_relationships', 'code'),
            $this->trans('resource_field_exists_in_attributes_and_relationships', 'title'),
            $this->trans('resource_field_exists_in_attributes_and_relationships', 'detail', compact('field')),
            $this->pointer($path)
        );
    }

    /**
     * Create an error for when a resource cannot be deleted.
     *
     * @param string|null $detail
     * @return ErrorInterface
     */
    public function resourceCannotBeDeleted(string $detail = null): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            null,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $this->trans('resource_cannot_be_deleted', 'code'),
            $this->trans('resource_cannot_be_deleted', 'title'),
            $detail ?: $this->trans('resource_cannot_be_deleted', 'detail')
        );
    }

    /**
     * Create an error for an invalid resource.
     *
     * @param string $path
     * @param string|null $detail
     *      the validation message (already translated).
     * @param array $failed
     *      rule failure details
     * @return ErrorInterface
     */
    public function invalidResource(
        string $path,
        ?string $detail = null,
        array $failed = []
    ): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            null,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $this->trans('resource_invalid', 'code'),
            $this->trans('resource_invalid', 'title'),
            $detail ?: $this->trans('resource_invalid', 'detail'),
            $this->pointer($path),
            !empty($failed),
            $failed ? compact('failed') : null
        );
    }

    /**
     * Create an error for an invalid query parameter.
     *
     * @param string $param
     * @param string|null $detail
     *      the validation message (already translated).
     * @param array $failed
     *      rule failure details.
     * @return ErrorInterface
     */
    public function invalidQueryParameter(string $param, ?string $detail = null, array $failed = []): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            null,
            Response::HTTP_BAD_REQUEST,
            $this->trans('query_invalid', 'code'),
            $this->trans('query_invalid', 'title'),
            $detail ?: $this->trans('query_invalid', 'detail'),
            [NeomerxError::SOURCE_PARAMETER => $param],
            !empty($failed),
            $failed ? compact('failed') : null
        );
    }

    /**
     * Create errors for a failed validator.
     *
     * @param ValidatorContract $validator
     * @param \Closure|null $closure
     *      a closure that is bound to the translator.
     * @return ErrorCollection
     */
    public function failedValidator(ValidatorContract $validator, \Closure $closure = null): ErrorCollection
    {
        $failed = $this->doesIncludeFailed() ? $validator->failed() : [];
        $errors = new ErrorCollection();

        foreach ($validator->errors()->messages() as $key => $messages) {
            $failures = $this->createValidationFailures($failed[$key] ?? []);

            foreach ($messages as $detail) {
                if ($closure) {
                    $currentFailure = $failures->shift() ?: [];
                    $errors->add($this->call($closure, $key, $detail, $currentFailure));
                    continue;
                }

                $errors->add(new NeomerxError(
                    null,
                    null,
                    null,
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    $this->trans('failed_validator', 'code'),
                    $this->trans('failed_validator', 'title'),
                    $detail ?: $this->trans('failed_validator', 'detail')
                ));
            }
        }

        return $errors;
    }

    /**
     * Create a JSON API exception for a failed validator.
     *
     * @param ValidatorContract $validator
     * @param \Closure|null $closure
     * @return JsonApiException
     */
    public function failedValidatorException(
        ValidatorContract $validator,
        \Closure $closure = null
    ): JsonApiException
    {
        return new ValidationException(
            $this->failedValidator($validator, $closure)
        );
    }

    /**
     * Create an error by calling the closure with it bound to the error translator.
     *
     * @param \Closure $closure
     * @param mixed ...$args
     * @return ErrorInterface
     */
    public function call(\Closure $closure, ...$args): ErrorInterface
    {
        return $closure->call($this, ...$args);
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
        $value = $this->translator->get(
            $key = "jsonapi::errors.{$key}.{$member}",
            $replace,
            $locale
        ) ?: null;

        return ($key !== $value) ? $value : null;
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
        /** Member can be '0' which is an empty string. */
        $withoutMember = is_null($member) || '' === $member;
        $pointer = !$withoutMember ? sprintf('%s/%s', rtrim($path, '/'), $member) : $path;

        return [NeomerxError::SOURCE_POINTER => $pointer];
    }

    /**
     * @return bool
     */
    protected function doesIncludeFailed(): bool
    {
        return $this->includeFailed;
    }

    /**
     * @param array $failures
     * @return Collection
     */
    protected function createValidationFailures(array $failures): Collection
    {
        return collect($failures)->map(function ($options, $rule) {
            return $this->createValidationFailure($rule, $options);
        })->values();
    }

    /**
     * @param string $rule
     * @param array|null $options
     * @return array
     */
    protected function createValidationFailure(string $rule, ?array $options): array
    {
        $failure = ['rule' => $this->convertRuleName($rule)];

        if (!empty($options) && $this->failedRuleHasOptions($rule)) {
            $failure['options'] = $options;
        }

        return $failure;
    }

    /**
     * @param string $rule
     * @return string
     */
    protected function convertRuleName(string $rule): string
    {
        return $this->translator->get(
            Str::dasherize(class_basename($rule))
        );
    }

    /**
     * Should options for the rule be displayed?
     *
     * @param string $rule
     * @return bool
     */
    protected function failedRuleHasOptions(string $rule): bool
    {
        return !\in_array(strtolower($rule), [
            'exists',
            'unique',
        ], true);
    }
}
