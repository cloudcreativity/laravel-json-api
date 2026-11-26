<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Document;

use CloudCreativity\LaravelJsonApi\Document\Link\Link;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;

class LinkTest extends TestCase
{
    public function testLinkWithoutMetadata(): void
    {
        $url = 'https://example.test/leads/123';

        $link = new Link($url);

        $this->assertNull($link->getMeta());
        $this->assertSame($url, $link->jsonSerialize());
        $this->assertSame(
            json_encode($url, JSON_THROW_ON_ERROR),
            json_encode($link, JSON_THROW_ON_ERROR)
        );
    }

    public function testLinkWithMetadata(): void
    {
        $url = 'https://example.test/leads/123';
        $meta = ['source' => 'test'];

        $link = new Link($url, $meta);

        $expected = [
            'href' => $url,
            'meta' => $meta,
        ];

        $this->assertSame($meta, $link->getMeta());
        $this->assertSame($expected, $link->jsonSerialize());
        $this->assertSame(
            json_encode($expected, JSON_THROW_ON_ERROR),
            json_encode($link, JSON_THROW_ON_ERROR)
        );
    }
}
