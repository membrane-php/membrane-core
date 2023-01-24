<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Exception\CannotProcessRequest;
use Membrane\OpenAPI\Method;
use Membrane\OpenAPI\Specification\Response;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Specification\Response
 * @covers \Membrane\OpenAPI\Specification\APISpec
 * @covers \Membrane\OpenAPI\Exception\CannotProcessOpenAPI
 * @covers \Membrane\OpenAPI\Exception\CannotProcessRequest
 * @uses   \Membrane\OpenAPI\PathMatcher
 */
class ResponseTest extends TestCase
{
    public const DIR = __DIR__ . '/../../fixtures/OpenAPI/';

    /**
     * @test
     */
    public function throwsExceptionIfApplicableResponseNotFound(): void
    {
        $httpStatus = '404';
        self::expectExceptionObject(CannotProcessOpenAPI::responseNotFound($httpStatus));

        new Response(self::DIR . 'noReferences.json', 'http://www.test.com/path', Method::GET, $httpStatus);
    }

    /**
     * @test
     */
    public function throwsExceptionIfResponseContentNotJson(): void
    {
        self::expectExceptionObject(CannotProcessRequest::unsupportedContent());

        new Response(self::DIR . 'noReferences.json', 'http://www.test.com/path', Method::PUT, '200');
    }

    /**
     * @test
     */
    public function returnsDefaultResponseIfExactMatchNotFound(): void
    {
        $class = new Response(self::DIR . 'noReferences.json', 'http://www.test.com/path', Method::DELETE, '404');

        self::assertInstanceOf(Schema::class, $class->schema);
    }

    /**
     * @test
     */
    public function schemaIsSchemaObjectIfContentJson(): void
    {
        $class = new Response(self::DIR . 'noReferences.json', 'http://www.test.com/path', Method::DELETE, 'default');

        self::assertInstanceOf(Schema::class, $class->schema);
    }

    public function dataSetsWithNullSchemas(): array
    {
        return [
            'response with no content' => [
                'http://www.test.com/path',
                Method::GET,
                '200',
                'noReferences.json',
            ],
            'response with empty content' => [
                'http://www.test.com/path',
                Method::POST,
                '200',
                'noReferences.json',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithNullSchemas
     */
    public function schemaIsNullIfResponseHasNoContentOrEmpty(
        string $url,
        Method $method,
        string $httpStatus,
        string $filePath
    ): void {
        $class = new Response(self::DIR . $filePath, $url, $method, $httpStatus);

        self::assertNull($class->schema);
    }

    public function dataSetsWithReferences(): array
    {
        return [
            [
                'http://www.test.com/path',
                Method::GET,
                '200',
                'references.json',
            ],
            [
                'http://www.test.com/path',
                Method::GET,
                '200',
                'references.yaml',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithReferences
     */
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
