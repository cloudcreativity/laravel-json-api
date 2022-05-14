<?php

declare(strict_types=1);

namespace CloudCreativity\LaravelJsonApi\Http\Headers;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\AcceptHeaderInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderParametersInterface;

class HeaderParameters implements HeaderParametersInterface
{
    /**
     * @var AcceptHeaderInterface
     */
    private AcceptHeaderInterface $accept;

    /**
     * @var HeaderInterface|null
     */
    private ?HeaderInterface $contentType;

    /**
     * HeaderParameters constructor.
     *
     * @param AcceptHeaderInterface $accept
     * @param HeaderInterface|null $contentType
     */
    public function __construct(AcceptHeaderInterface $accept, HeaderInterface $contentType = null)
    {
        $this->accept = $accept;
        $this->contentType = $contentType;
    }

    /**
     * @inheritdoc
     */
    public function getAcceptHeader(): AcceptHeaderInterface
    {
        return $this->accept;
    }

    /**
     * @inheritdoc
     */
    public function getContentTypeHeader(): ?HeaderInterface
    {
        return $this->contentType;
    }
}
