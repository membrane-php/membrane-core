<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Specification\OpenAPIResponse;
use Membrane\OpenAPIReader\OpenAPIVersion;
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
        $response = new Response([
            'description' => 'Success',
            'content' => [
                'application/pdf' => new MediaType(['schema' => new Schema(['type' => 'integer'])]),
            ],
        ]);

        self::expectExceptionObject(CannotProcessOpenAPI::unsupportedMediaTypes(...array_keys($response->content)));

        new OpenAPIResponse(OpenAPIVersion::Version_3_0, 'testOperation', '200', $response);
    }

    #[Test, TestDox('$schema contains a Schema Object if the response has content')]
    public function schemaIsNullIfContentDoesNotExist(): void
    {
        $response = new Response([
            'description' => 'Success',
        ]);

        $sut = new OpenAPIResponse(OpenAPIVersion::Version_3_0, 'testOperation', '200', $response);

        self::assertNull($sut->schema);
    }

    #[Test, TestDox('$schema contains a Schema Object if the response has empty content')]
    public function schemaIsNullIfContentIsEmpty(): void
    {
        $response = new Response([
            'description' => 'Success',
            'content' => [],
        ]);

        $sut = new OpenAPIResponse(OpenAPIVersion::Version_3_0, 'testOperation', '200', $response);

        self::assertNull($sut->schema);
    }

    #[Test, TestDox('$schema contains a Schema Object if the response has content')]
    public function schemaContainsResponseSchemaIfContentExists(): void
    {
        $expected = new Schema(['type' => 'integer']);
        $response = new Response([
            'description' => 'Success',
            'content' => [
                'application/json' => new MediaType(['schema' => new Schema(['type' => 'integer'])]),
            ],
        ]);

        $sut = new OpenAPIResponse(OpenAPIVersion::Version_3_0, 'testOperation', '200', $response);

        self::assertEquals($expected, $sut->schema);
    }
}
