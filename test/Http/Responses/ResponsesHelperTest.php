<?php

namespace CloudCreativity\JsonApi\Http\Responses;

use CloudCreativity\JsonApi\Contracts\Error\ErrorObjectInterface;
use CloudCreativity\JsonApi\Error\ErrorCollection;
use CloudCreativity\JsonApi\Error\ErrorObject;
use CloudCreativity\JsonApi\Integration\LaravelIntegration;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Encoder\Encoder;
use Neomerx\JsonApi\Parameters\EncodingParameters;
use CloudCreativity\JsonApi\Contracts\Integration\EnvironmentInterface;
use Neomerx\JsonApi\Parameters\Headers\MediaType;
use Neomerx\JsonApi\Responses\Responses;

class ResponsesHelperTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Encoder
     */
    private $encoder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $environment;

    /**
     * @var ResponsesHelper
     */
    private $helper;

    protected function setUp()
    {
        $this->encoder = Encoder::instance([]);
        $encodingParameters = new EncodingParameters(['foo']);
        $mediaType = new MediaType(MediaType::JSON_API_TYPE, MediaType::JSON_API_SUB_TYPE);

        $environment = $this->getMock(EnvironmentInterface::class);
        $environment->method('getEncoder')->willReturn($this->encoder);
        $environment->method('getParameters')->willReturn($encodingParameters);
        $environment->method('getEncoderMediaType')->willReturn($mediaType);
        $this->environment = $environment;

        $integration = new LaravelIntegration(new Request());
        $responses = new Responses($integration);

        /** @var EnvironmentInterface $environment */
        $this->helper = new ResponsesHelper($environment, $responses);
    }

    public function testError()
    {
        $error = new ErrorObject([
            ErrorObject::TITLE => 'Test error',
            ErrorObject::DETAIL => 'My custom error',
            ErrorObject::CODE => 'foo-bar',
            ErrorObject::STATUS => 501,
        ]);

        $expected = new Response($this->encoder->encodeError($error), 501, [
            'Content-Type' => MediaType::JSON_API_MEDIA_TYPE,
        ]);

        $this->assertEquals($expected, $this->helper->error($error));

        $expected->header('X-Custom', 'Foo');

        $this->assertEquals($expected, $this->helper->error($error, [
            'X-Custom' => 'Foo',
        ]));
    }

    public function testErrorsWithArray()
    {
        $errors = [new ErrorObject([
            ErrorObject::TITLE => 'Error',
            ErrorObject::CODE => 'foo-bar',
            ErrorObject::STATUS => 418,
        ])];

        $content = $this->encoder->encodeErrors($errors);

        $expected = new Response($content, 500, [
            'Content-Type' => MediaType::JSON_API_MEDIA_TYPE,
        ]);

        $this->assertEquals($expected, $this->helper->errors($errors));

        $expected->setStatusCode(418);
        $expected->header('X-Custom', 'Foo');

        $this->assertEquals($expected, $this->helper->errors($errors, 418, [
            'X-Custom' => 'Foo',
        ]));
    }

    public function testErrorsWithCollection()
    {
        $errors = new ErrorCollection();
        $errors->error([
            ErrorObject::TITLE => 'Error',
            ErrorObject::DETAIL => 'Foo',
            ErrorObject::STATUS => 418,
        ]);

        $content = $this->encoder->encodeErrors($errors->getAll());

        $expected = new Response($content, $errors->getStatus(), [
            'Content-Type' => MediaType::JSON_API_MEDIA_TYPE,
        ]);

        $this->assertEquals($expected, $this->helper->errors($errors));

        $expected->setStatusCode(499);
        $expected->header('X-Custom', 'Foo');

        $this->assertEquals($expected, $this->helper->errors($errors, 499, [
            'X-Custom' => 'Foo',
        ]));
    }
}
