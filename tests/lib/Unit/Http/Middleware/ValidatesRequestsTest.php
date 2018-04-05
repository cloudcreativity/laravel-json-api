<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Http\Middleware;

use CloudCreativity\JsonApi\Contracts\Http\Requests\InboundRequestInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Contracts\Validators\DocumentValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorProviderInterface;
use CloudCreativity\JsonApi\Document\Error;
use CloudCreativity\JsonApi\Exceptions\ValidationException;
use CloudCreativity\JsonApi\Http\Middleware\ValidatesRequests;
use CloudCreativity\JsonApi\Object\Document;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use Neomerx\JsonApi\Contracts\Http\Query\QueryCheckerInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

class ValidatesRequestsTest extends TestCase
{

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|StoreInterface
     */
    private $store;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|QueryCheckerInterface
     */
    private $queryChecker;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DocumentValidatorInterface
     */
    private $validator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ValidatorProviderInterface
     */
    private $providers;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ValidatorProviderInterface
     */
    private $inverse;

    /**
     * @var InboundRequestInterface
     */
    private $request;

    /**
     * @var \stdClass
     */
    private $record;

    /**
     * @var ValidatesRequests
     */
    private $trait;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->store = $this->createMock(StoreInterface::class);
        $this->queryChecker = $this->createMock(QueryCheckerInterface::class);
        $this->providers = $this->createMock(ValidatorProviderInterface::class);
        $this->validator = $this->createMock(DocumentValidatorInterface::class);
        $this->record = new \stdClass();
        $this->trait = $this->getMockForTrait(ValidatesRequests::class);
    }

    public function testIndex()
    {
        $this->request = $this->factory->createInboundRequest(
            'GET',
            'posts'
        );

        $this->withoutRecord()
            ->withResourceQueryChecker('searchQueryChecker')
            ->willNotCheckDocument()
            ->doValidate();
    }

    public function testCreate()
    {
        $this->request = $this->factory->createInboundRequest(
            'POST',
            'posts',
            null,
            null,
            false,
            new Document()
        );

        $this->withoutRecord()
            ->withResourceQueryChecker('resourceQueryChecker')
            ->willCheckDocument('createResource')
            ->doValidate();
    }

    public function testCreateWithInvalidDocument()
    {
        $this->request = $this->factory->createInboundRequest(
            'POST',
            'posts',
            null,
            null,
            false,
            new Document()
        );

        $this->withoutRecord()
            ->withResourceQueryChecker('resourceQueryChecker')
            ->willCheckDocument('createResource', false)
            ->doValidateWithException();
    }

    public function testRead()
    {
        $this->request = $this->factory->createInboundRequest(
            'GET',
            'posts',
            '1'
        );

        $this->withRecord()
            ->withResourceQueryChecker('resourceQueryChecker')
            ->willNotCheckDocument()
            ->doValidate();
    }

    public function testUpdate()
    {
        $this->request = $this->factory->createInboundRequest(
            'PATCH',
            'posts',
            '1',
            null,
            false,
            new Document()
        );

        $this->withRecord()
            ->withResourceQueryChecker('resourceQueryChecker')
            ->willCheckDocument('updateResource')
            ->doValidate();
    }

    public function testUpdateWithInvalidDocument()
    {
        $this->request = $this->factory->createInboundRequest(
            'PATCH',
            'posts',
            '1',
            null,
            false,
            new Document()
        );

        $this->withRecord()
            ->withResourceQueryChecker('resourceQueryChecker')
            ->willCheckDocument('updateResource', false)
            ->doValidateWithException();
    }

    public function testDelete()
    {
        $this->request = $this->factory->createInboundRequest(
            'DELETE',
            'posts',
            '1'
        );

        $this->withRecord()
            ->withResourceQueryChecker('resourceQueryChecker')
            ->willNotCheckDocument()
            ->doValidate();
    }

    public function testReadRelated()
    {
        $this->request = $this->factory->createInboundRequest(
            'GET',
            'posts',
            '1',
            'comments',
            false
        );

        $this->withRecord()
            ->withRelatedQueryChecker('relatedQueryChecker')
            ->willNotCheckDocument()
            ->doValidate();
    }

    /**
     * If no related query checker is provided, then the query parameters
     * are not checked.
     */
    public function testReadRelatedWithoutRelatedQueryChecker()
    {
        $this->request = $this->factory->createInboundRequest(
            'GET',
            'posts',
            '1',
            'comments',
            false
        );

        $this->withRecord()
            ->willNotCheckDocument()
            ->doValidate();
    }

    public function testReadRelationship()
    {
        $this->request = $this->factory->createInboundRequest(
            'GET',
            'posts',
            '1',
            'comments',
            true
        );

        $this->withRecord()
            ->withRelatedQueryChecker('relationshipQueryChecker')
            ->willNotCheckDocument()
            ->doValidate();
    }

    public function testReplaceRelationship()
    {
        $this->request = $this->factory->createInboundRequest(
            'POST',
            'posts',
            '1',
            'comments',
            true,
            new Document()
        );

        $this->withRecord()
            ->withRelatedQueryChecker('relationshipQueryChecker')
            ->willCheckDocument('modifyRelationship')
            ->doValidate();
    }

    public function testReplaceRelationshipWithInvalidDocument()
    {
        $this->request = $this->factory->createInboundRequest(
            'POST',
            'posts',
            '1',
            'comments',
            true,
            new Document()
        );

        $this->withRecord()
            ->withRelatedQueryChecker('relationshipQueryChecker')
            ->willCheckDocument('modifyRelationship', false)
            ->doValidateWithException();
    }

    public function testAddToRelationship()
    {
        $this->request = $this->factory->createInboundRequest(
            'PATCH',
            'posts',
            '1',
            'comments',
            true,
            new Document()
        );

        $this->withRecord()
            ->withRelatedQueryChecker('relationshipQueryChecker')
            ->willCheckDocument('modifyRelationship')
            ->doValidate();
    }

    public function testAddToRelationshipWithInvalidDocument()
    {
        $this->request = $this->factory->createInboundRequest(
            'PATCH',
            'posts',
            '1',
            'comments',
            true,
            new Document()
        );

        $this->withRecord()
            ->withRelatedQueryChecker('relationshipQueryChecker')
            ->willCheckDocument('modifyRelationship', false)
            ->doValidateWithException();
    }

    public function testRemoveFromRelationship()
    {
        $this->request = $this->factory->createInboundRequest(
            'DELETE',
            'posts',
            '1',
            'comments',
            true,
            new Document()
        );

        $this->withRecord()
            ->withRelatedQueryChecker('relationshipQueryChecker')
            ->willCheckDocument('modifyRelationship')
            ->doValidate();
    }

    public function testRemoveFromRelationshipWithInvalidDocument()
    {
        $this->request = $this->factory->createInboundRequest(
            'DELETE',
            'posts',
            '1',
            'comments',
            true,
            new Document()
        );

        $this->withRecord()
            ->withRelatedQueryChecker('relationshipQueryChecker')
            ->willCheckDocument('modifyRelationship', false)
            ->doValidateWithException();
    }

    /**
     * @return $this
     */
    private function withoutRecord()
    {
        $this->store->expects($this->never())->method('findOrFail');

        return $this;
    }

    /**
     * @return $this
     */
    private function withRecord()
    {
        if (!$identifier = $this->request->getResourceIdentifier()) {
            $this->fail('Request must have an identifier.');
        }

        $this->store
            ->expects($this->once())
            ->method('findOrFail')
            ->with($identifier)
            ->willReturn($this->record);

        return $this;
    }

    /**
     * @param $method
     * @return $this
     */
    private function withResourceQueryChecker($method)
    {
        $this->providers->expects($this->once())->method($method)->willReturn($this->queryChecker);
        $this->willCheckQuery();

        return $this;
    }

    /**
     * @param $method
     * @return $this
     */
    private function withRelatedQueryChecker($method)
    {
        $this->inverse = $this->createMock(ValidatorProviderInterface::class);
        $this->inverse->expects($this->once())->method($method)->willReturn($this->queryChecker);
        $this->willCheckQuery();

        return $this;
    }

    /**
     * @return $this
     */
    private function willCheckQuery()
    {
        $this->queryChecker
            ->expects($this->once())
            ->method('checkQuery')
            ->with($this->request->getParameters());

        return $this;
    }

    /**
     * @return $this
     */
    private function willNotCheckDocument()
    {
        $this->providers->expects($this->never())->method('createResource');
        $this->providers->expects($this->never())->method('updateResource');
        $this->providers->expects($this->never())->method('modifyRelationship');

        return $this;
    }

    private function willCheckDocument($method, $valid = true)
    {
        $this->providers
            ->expects($this->once())
            ->method($method)
            ->willReturn($this->validator);

        $this->validator
            ->expects($this->once())
            ->method('isValid')
            ->willReturn($valid);

        if (!$valid) {
            $this->withErrorMessages();
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function withErrorMessages()
    {
        $this->validator
            ->expects($this->once())
            ->method('getErrors')
            ->willReturn($errors = new ErrorCollection());

        $errors->add(Error::create()->setCode('my-error'));

        return $this;

    }

    /**
     * @return void
     */
    private function doValidate()
    {
        $this->trait->validate($this->request, $this->store, $this->providers, $this->inverse);
    }

    /**
     * @return void
     */
    private function doValidateWithException()
    {
        try {
            $this->doValidate();
            $this->fail('No exception thrown.');
        } catch (ValidationException $e) {
            $this->assertSame('my-error', current($e->getErrors()->getArrayCopy())->getCode());
        }
    }
}
