<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use GuzzleHttp\Psr7\ServerRequest;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\ExtractPathParameters\PathMatcher;
use Membrane\OpenAPI\Specification\Request;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request::class)]
#[CoversClass(CannotProcessOpenAPI::class)]
#[CoversClass(CannotProcessSpecification::class)]
#[UsesClass(PathMatcher::class)]
class RequestTest extends TestCase
{
    public const DIR = __DIR__ . '/../../fixtures/OpenAPI/';

    #[Test]
    public function fromPsr7ThrowsExceptionIfUnsupportedMethod(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::methodNotSupported('UPDATE'));

        $serverRequest = new ServerRequest('UPDATE', 'http://test.com/path');

        Request::fromPsr7(self::DIR . 'noReferences.json', $serverRequest);
    }

    #[Test]
    public function fromPsr7SuccessfulConstructionTest(): void
    {
        $expected = new Request(self::DIR . 'noReferences.json', '/path', Method::GET);
        $serverRequest = new ServerRequest('GET', 'http://test.com/path');

        $actual = Request::fromPsr7(self::DIR . 'noReferences.json', $serverRequest);

        self::assertEquals($expected, $actual);
    }
}
