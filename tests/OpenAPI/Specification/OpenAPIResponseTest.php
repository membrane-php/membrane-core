<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Specification\OpenAPIResponse;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V30;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenAPIResponse::class)]
#[CoversClass(CannotProcessOpenAPI::class)]
class OpenAPIResponseTest extends TestCase
{
    #[Test, TestDox('throws Exception if content exists but there are no supported MediaTypes')]
    public function throwsExceptionIfContentExistsWithoutSupportedMediaTypes(): void
    {
        $response = new V30\Response(new Identifier('test'), new Partial\Response(
            description: 'Success',
            headers: [],
            content: ['application/pdf' => new Partial\MediaType(
                contentType: 'application/pdf',
                schema: new Partial\Schema(type: 'string'),
            )]
        ));

        self::expectExceptionObject(CannotProcessOpenAPI::unsupportedMediaTypes(...array_keys($response->content)));

        new OpenAPIResponse('testOperation', '200', $response);
    }

    #[Test, TestDox('$schema contains a Schema Object if the response has empty content')]
    public function schemaIsNullIfContentIsEmpty(): void
    {
        $response = new V30\Response(new Identifier('test'), new Partial\Response(
            description: 'Success',
            headers: [],
            content: []
        ));

        $sut = new OpenAPIResponse('testOperation', '200', $response);

        self::assertNull($sut->schema);
    }

    #[Test, TestDox('$schema contains a Schema Object if the response has content')]
    public function schemaContainsResponseSchemaIfContentExists(): void
    {
        $expected = new V30\Schema(
            new Identifier('test', 'application/json', 'schema'),
            new Partial\Schema(type: 'integer')
        );

        $response = new V30\Response(new Identifier('test'), new Partial\Response(
            description: 'Success',
            headers: [],
            content: ['application/pdf' => new Partial\MediaType(
                contentType: 'application/json',
                schema: new Partial\Schema(type: 'integer'),
            )]
        ));

        $sut = new OpenAPIResponse('testOperation', '200', $response);

        self::assertEquals($expected, $sut->schema);
    }
}
