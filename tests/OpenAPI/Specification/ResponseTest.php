<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Exception\CannotProcessRequest;
use Membrane\OpenAPI\Method;
use Membrane\OpenAPI\PathMatcher;
use Membrane\OpenAPI\Reader\OpenAPIFileReader;
use Membrane\OpenAPI\Specification\APISpec;
use Membrane\OpenAPI\Specification\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Response::class)]
#[CoversClass(APISpec::class)]
#[CoversClass(CannotProcessOpenAPI::class)]
#[CoversClass(CannotProcessRequest::class)]
#[UsesClass(PathMatcher::class)]
#[UsesClass(OpenAPIFileReader::class)]
class ResponseTest extends TestCase
{
    public const DIR = __DIR__ . '/../../fixtures/OpenAPI/';

    #[Test]
    public function throwsExceptionIfApplicableResponseNotFound(): void
    {
        $httpStatus = '404';
        self::expectExceptionObject(CannotProcessOpenAPI::responseNotFound($httpStatus));

        new Response(self::DIR . 'noReferences.json', 'http://test.com/path', Method::GET, $httpStatus);
    }

    #[Test]
    public function throwsExceptionIfResponseContentNotJson(): void
    {
        self::expectExceptionObject(CannotProcessRequest::unsupportedContent());

        new Response(self::DIR . 'noReferences.json', 'http://test.com/path', Method::PUT, '200');
    }

    #[Test]
    public function returnsDefaultResponseIfExactMatchNotFound(): void
    {
        $class = new Response(self::DIR . 'noReferences.json', 'http://test.com/path', Method::DELETE, '404');

        self::assertInstanceOf(Schema::class, $class->schema);
    }

    #[Test]
    public function schemaIsSchemaObjectIfContentJson(): void
    {
        $class = new Response(self::DIR . 'noReferences.json', 'http://test.com/path', Method::DELETE, 'default');

        self::assertInstanceOf(Schema::class, $class->schema);
    }

    public static function dataSetsWithNullSchemas(): array
    {
        return [
            'response with no content' => [
                'http://test.com/path',
                Method::GET,
                '200',
                'noReferences.json',
            ],
            'response with empty content' => [
                'http://test.com/path',
                Method::POST,
                '200',
                'noReferences.json',
            ],
        ];
    }

    #[DataProvider('dataSetsWithNullSchemas')]
    #[Test]
    public function schemaIsNullIfResponseHasNoContentOrEmpty(
        string $url,
        Method $method,
        string $httpStatus,
        string $filePath
    ): void {
        $class = new Response(self::DIR . $filePath, $url, $method, $httpStatus);

        self::assertNull($class->schema);
    }

    public static function dataSetsWithReferences(): array
    {
        return [
            [
                'http://test.com/path',
                Method::GET,
                '200',
                'references.json',
            ],
            [
                'http://test.com/path',
                Method::GET,
                '200',
                'references.yaml',
            ],
        ];
    }

    #[DataProvider('dataSetsWithReferences')]
    #[Test]
    public function ResponseSchemaReferencesResolved(
        string $url,
        Method $method,
        string $httpStatus,
        string $filePath
    ): void {
        $class = new Response(self::DIR . $filePath, $url, $method, $httpStatus);

        self::assertInstanceOf(Schema::class, $class->schema);
    }
}
