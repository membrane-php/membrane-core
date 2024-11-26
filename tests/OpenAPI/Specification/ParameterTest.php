<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Specification\Parameter;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30};
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(Parameter::class)]
#[CoversClass(CannotProcessOpenAPI::class)]
class ParameterTest extends TestCase
{
    #[Test, TestDox('Exceptions will be thrown for parameters with unsupported content types')]
    public function throwsExceptionForUnsupportedContentTypes(): void
    {
        $parameter = new V30\Parameter(new Identifier('test'), new Partial\Parameter(
            name: 'test-param',
            in: 'query',
            content: [new Partial\MediaType(contentType: 'application/pdf', schema: new Partial\Schema())],
        ));

        self::expectExceptionObject(CannotProcessOpenAPI::unsupportedMediaTypes('application/pdf'));

        new Parameter(OpenAPIVersion::Version_3_0, $parameter);
    }

    public static function provideValidParameters(): array
    {
        return [
            '"style": "matrix" in "path"' => [
                OpenAPIVersion::Version_3_0,
                new V30\Parameter(new Identifier('test'), new Partial\Parameter(
                    name: 'id',
                    in: 'path',
                    required: true,
                    style: 'matrix',
                    schema: new Partial\Schema(type: 'integer'),
                )),
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'style' => 'matrix',
                    'explode' => false,
                    'schema' => new V30\Schema(
                        new Identifier('test', 'id(path)', 'schema'),
                        new Partial\Schema(type: 'integer'),
                    ),
                ],
            ],
            '"style": "label" in "path"' => [
                OpenAPIVersion::Version_3_0,
                new V30\Parameter(new Identifier('test'), new Partial\Parameter(
                    name: 'id',
                    in: 'path',
                    required: true,
                    style: 'label',
                    schema: new Partial\Schema(type: 'integer'),
                )),
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'style' => 'label',
                    'explode' => false,
                    'schema' => new V30\Schema(
                        new Identifier('test', 'id(path)', 'schema'),
                        new Partial\Schema(type: 'integer'),
                    ),
                ],
            ],
            '"style":"form" in "query"' => [
                OpenAPIVersion::Version_3_0,
                new V30\Parameter(new Identifier('test'), new Partial\Parameter(
                    name: 'id',
                    in: 'query',
                    required: false,
                    style: 'form',
                    schema: new Partial\Schema(type: 'integer'),
                )),
                [
                    'name' => 'id',
                    'in' => 'query',
                    'required' => false,
                    'style' => 'form',
                    'explode' => true,
                    'schema' => new V30\Schema(
                        new Identifier('test', 'id(query)', 'schema'),
                        new Partial\Schema(type: 'integer'),
                    ),
                ],
            ],
            '"style":"simple" in "path"' => [
                OpenAPIVersion::Version_3_0,
                new V30\Parameter(new Identifier('test'), new Partial\Parameter(
                    name: 'id',
                    in: 'path',
                    required: true,
                    style: 'simple',
                    schema: new Partial\Schema(type: 'integer'),
                )),
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'style' => 'simple',
                    'explode' => false,
                    'schema' => new V30\Schema(
                        new Identifier('test', 'id(path)', 'schema'),
                        new Partial\Schema(type: 'integer'),
                    ),
                ],
            ],
            '"style":"spaceDelimited" in "query"' => [
                OpenAPIVersion::Version_3_0,
                new V30\Parameter(new Identifier('test'), new Partial\Parameter(
                    name: 'id',
                    in: 'query',
                    required: false,
                    style: 'spaceDelimited',
                    schema: new Partial\Schema(type: 'integer'),
                )),
                [
                    'name' => 'id',
                    'in' => 'query',
                    'required' => false,
                    'style' => 'spaceDelimited',
                    'explode' => false,
                    'schema' => new V30\Schema(
                        new Identifier('test', 'id(query)', 'schema'),
                        new Partial\Schema(type: 'integer'),
                    ),
                ],
            ],
            '"style":"pipeDelimited" in "query"' => [
                OpenAPIVersion::Version_3_0,
                new V30\Parameter(new Identifier('test'), new Partial\Parameter(
                    name: 'id',
                    in: 'query',
                    required: false,
                    style: 'pipeDelimited',
                    schema: new Partial\Schema(type: 'integer'),
                )),
                [
                    'name' => 'id',
                    'in' => 'query',
                    'required' => false,
                    'style' => 'pipeDelimited',
                    'explode' => false,
                    'schema' => new V30\Schema(
                        new Identifier('test', 'id(query)', 'schema'),
                        new Partial\Schema(type: 'integer'),
                    ),
                ],
            ],
            '"style":"deepObject" in "query"' => [
                OpenAPIVersion::Version_3_0,
                new V30\Parameter(new Identifier('test'), new Partial\Parameter(
                    name: 'id',
                    in: 'query',
                    required: false,
                    style: 'deepObject',
                    explode: false,
                    schema: new Partial\Schema(type: 'object'),
                )),
                [
                    'name' => 'id',
                    'in' => 'query',
                    'required' => false,
                    'style' => 'deepObject',
                    'explode' => false,
                    'schema' => new V30\Schema(
                        new Identifier('test', 'id(query)', 'schema'),
                        new Partial\Schema(type: 'object'),
                    ),
                ],
            ],
        ];
    }

    #[Test]
    #[TestDox('It will construct itself from valid Parameters')]
    #[DataProvider('provideValidParameters')]
    public function itConstructsFromValidParameters(
        OpenAPIVersion $openApiVersion,
        V30\Parameter|V31\Parameter $parameter,
        array $expectedProperties
    ): void {
        $sut = new Parameter($openApiVersion, $parameter);

        foreach ($expectedProperties as $key => $value) {
            self::assertEquals($value, $sut->$key, sprintf("'%s' doesn't match expected", $key));
        }
    }
}
