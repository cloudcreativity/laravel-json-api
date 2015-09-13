<?php

namespace CloudCreativity\JsonApi\Http\Controllers;

use App;
use CloudCreativity\JsonApi\Contracts\Object\Document\DocumentInterface;
use CloudCreativity\JsonApi\Contracts\Validator\ValidatorAwareInterface;
use CloudCreativity\JsonApi\Contracts\Validator\ValidatorInterface;
use CloudCreativity\JsonApi\Object\Document\Document;
use CloudCreativity\JsonApi\Validator\Document\DocumentValidator;
use Illuminate\Http\Request;
use JsonApi;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Decoder\DecoderInterface;
use Neomerx\JsonApi\Contracts\Integration\ExceptionThrowerInterface;
use RuntimeException;

/**
 * Class DocumentDecoderTrait
 * @package CloudCreativity\JsonApi
 */
trait DocumentDecoderTrait
{

    /**
     * @param ValidatorInterface|null $validator
     * @return mixed
     */
    public function getContentBody(ValidatorInterface $validator = null)
    {
        /** @var CodecMatcherInterface $codecMatcher */
        $codecMatcher = JsonApi::getCodecMatcher();
        $decoder = $codecMatcher->getDecoder();

        if (!$decoder instanceof DecoderInterface) {
            /** @var ExceptionThrowerInterface $thrower */
            $thrower = App::make(ExceptionThrowerInterface::class);
            $thrower->throwBadRequest();
        }

        if ($validator && !$decoder instanceof ValidatorAwareInterface) {
            throw new RuntimeException('To use a validator on content body, your decoder must implement the ValidatorAwareInterface.');
        } elseif ($validator) {
            $decoder->setValidator($validator);
        }

        /** @var Request $request */
        $request = App::make('request');

        return $decoder->decode($request->getContent());
    }

    /**
     * @param ValidatorInterface|null $documentValidator
     * @return DocumentInterface
     */
    public function getDocumentObject(ValidatorInterface $documentValidator = null)
    {
        $content = $this->getContentBody($documentValidator);

        return ($content instanceof DocumentInterface) ? $content : new Document($content);
    }

    /**
     * @param ValidatorInterface|null $resourceValidator
     *      the validator for the "data" member in the document, which is expected to be a resource object.
     * @return \CloudCreativity\JsonApi\Contracts\Object\Resource\ResourceObjectInterface
     */
    public function getResourceObject(ValidatorInterface $resourceValidator = null)
    {
        $validator = ($resourceValidator) ? new DocumentValidator($resourceValidator) : null;

        return $this
            ->getDocumentObject($validator)
            ->getResourceObject();
    }

    /**
     * @param ValidatorInterface|null $relationshipValidator
     * @return \CloudCreativity\JsonApi\Contracts\Object\Relationships\RelationshipInterface
     */
    public function getRelationshipObject(ValidatorInterface $relationshipValidator = null)
    {
        return $this
            ->getDocumentObject($relationshipValidator)
            ->getRelationship();
    }
}
