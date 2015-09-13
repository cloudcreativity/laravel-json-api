<?php

namespace CloudCreativity\JsonApi\Http\Controllers;

use CloudCreativity\JsonApi\Validator\Relationships\BelongsToValidator;
use CloudCreativity\JsonApi\Validator\Relationships\HasManyValidator;
use CloudCreativity\JsonApi\Validator\Resource\IlluminateResourceValidator;

/**
 * Class DocumentValidatorTrait
 * @package CloudCreativity\JsonApi
 */
trait DocumentValidatorTrait
{

    use DocumentDecoderTrait;

    /**
     * @see CloudCreativity\JsonApi\Validator\Resource\IlluminateResourceValidator
     * @param string $expectedType
     *      the resource type that is expected.
     * @param string|int|null $expectedId
     *      the resource id that is expected, or null if validating a new resource.
     * @param array $attributesValidationRules
     *      Laravel validation rules for the resource's attributes.
     * @param array $attributesValidationMessages
     *      Laravel validation messages for the resource's attributes.
     * @param bool $attributesMemberRequired
     *      Whether an attributes member is expected in the resource received from the client
     * @return IlluminateResourceValidator
     */
    public function getResourceObjectValidator(
        $expectedType,
        $expectedId = null,
        array $attributesValidationRules = [],
        array $attributesValidationMessages = [],
        $attributesMemberRequired = true
    ) {
        return new IlluminateResourceValidator(
            $expectedType,
            $expectedId,
            $attributesValidationRules,
            $attributesValidationMessages,
            $attributesMemberRequired
        );
    }

    /**
     * @param string|string[]|null $expectedTypeOrTypes
     * @return BelongsToValidator
     */
    public function getHasOneValidator($expectedTypeOrTypes = null)
    {
        return new BelongsToValidator($expectedTypeOrTypes);
    }

    /**
     * @param string|string[]|null $expectedTypeOrTypes
     * @return HasManyValidator
     */
    public function getHasManyValidator($expectedTypeOrTypes = null)
    {
        return new HasManyValidator($expectedTypeOrTypes);
    }
}
