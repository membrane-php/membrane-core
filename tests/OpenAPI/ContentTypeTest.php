<?php

declare(strict_types=1);

namespace OpenAPI;

use Membrane\OpenAPI\ContentType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContentType::class)]
final class ContentTypeTest extends TestCase
{
    public static function provideContentTypeHeaders(): \Generator
    {
        yield 'no content type header' => [false, ContentType::Unmatched];
        yield 'application/json' => ['application/json', ContentType::Json];
        yield 'application/problem+json' => ['application/problem+json', ContentType::Json];
        yield 'image/png' => ['image/png', ContentType::Unmatched];
        yield 'form data' => ['application/x-www-form-urlencoded', ContentType::FormData];
        yield 'multipart content type' => ['multipart/form-data', ContentType::Multipart];
    }

    #[Test]
    #[DataProvider('provideContentTypeHeaders')]
    public function testFromContentTypeHeader(string|false $contentTypeHeader, ContentType $expected): void
    {
        $sut = ContentType::fromContentTypeHeader($contentTypeHeader);

        self::assertEquals($expected, $sut);
    }
}
