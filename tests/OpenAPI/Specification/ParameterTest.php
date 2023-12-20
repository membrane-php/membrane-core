<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec as Cebe;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Specification\Parameter;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\Reader;
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
        $openAPIFilePath = __DIR__ . '/../../fixtures/OpenAPI/noReferences.json';
        $openApi = (new Reader([OpenAPIVersion::Version_3_0]))->readFromAbsoluteFilePath($openAPIFilePath);
        $parameter = $openApi->paths->getPath('/requestpathexceptions')->post->parameters[0];

        self::expectExceptionObject(CannotProcessOpenAPI::unsupportedMediaTypes('application/pdf'));

        new Parameter($parameter);
    }

    public static function provideParametersWithInvalidStyleLocations(): array
    {
        return [
            '"style": "matrix" outside of "path"' => [
                new Cebe\Parameter([
                    'name' => 'id',
                    'in' => 'query',
                    'style' => 'matrix',
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ]),
                CannotProcessOpenAPI::invalidStyleLocation('id', 'matrix', 'query'),
            ],
            '"style": "label" outside of "path"' => [
                new Cebe\Parameter([
                    'name' => 'id',
                    'in' => 'query',
                    'style' => 'label',
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ]),
                CannotProcessOpenAPI::invalidStyleLocation('id', 'label', 'query'),
            ],
            '"style":"form" outside of "query" or "cookie"' => [
                new Cebe\Parameter([
                    'name' => 'id',
                    'in' => 'path',
                    'style' => 'form',
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ]),
                CannotProcessOpenAPI::invalidStyleLocation('id', 'form', 'path'),
            ],
            '"style":"simple" outside of "path" or "header"' => [
                new Cebe\Parameter([
                    'name' => 'id',
                    'in' => 'query',
                    'style' => 'simple',
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ]),
                CannotProcessOpenAPI::invalidStyleLocation('id', 'simple', 'query'),
            ],
            '"style":"spaceDelimited" outside of "query"' => [
                new Cebe\Parameter([
                    'name' => 'id',
                    'in' => 'path',
                    'style' => 'spaceDelimited',
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ]),
                CannotProcessOpenAPI::invalidStyleLocation('id', 'spaceDelimited', 'path'),
            ],
            '"style":"pipeDelimited" outside of "query"' => [
                new Cebe\Parameter([
                    'name' => 'id',
                    'in' => 'path',
                    'style' => 'pipeDelimited',
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ]),
                CannotProcessOpenAPI::invalidStyleLocation('id', 'pipeDelimited', 'path'),
            ],
            '"style":"deepObject" outside of "query"' => [
                new Cebe\Parameter([
                    'name' => 'id',
                    'in' => 'path',
                    'style' => 'deepObject',
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ]),
                CannotProcessOpenAPI::invalidStyleLocation('id', 'deepObject', 'path'),
            ],
        ];
    }

    #[Test, TestDox('throws CannotProcessOpenAPI if "style" is invalid based on "in" value')]
    #[DataProvider('provideParametersWithInvalidStyleLocations')]
    public function throwsExceptionForInvalidStyleLocations(
        Cebe\Parameter $parameter,
        CannotProcessOpenAPI $expected
    ): void {
        self::expectExceptionObject($expected);

        new Parameter($parameter);
    }

    #[Test, TestDox('throws CannotProcessOpenAPI if "explode":true if the parameter is of "type": "array" or "object"')]
    public function throwsExceptionForExplodeOnArraysAndObjects(): void
    {
        $parameter = new Cebe\Parameter([
            'name' => 'tags',
            'in' => 'query',
            'explode' => true,
            'schema' => new Cebe\Schema(['type' => 'array']),
        ]);

        self::expectExceptionObject(CannotProcessOpenAPI::unsupportedStyle('tags', 'form'));

        new Parameter($parameter);
    }

    public static function provideValidParameters(): array
    {
        return [
            '"style": "matrix" in "path"' => [
                new Cebe\Parameter([
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'style' => 'matrix',
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ]),
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'style' => 'matrix',
                    'explode' => false,
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ],
            ],
            '"style": "label" in "path"' => [
                new Cebe\Parameter([
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'style' => 'label',
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ]),
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'style' => 'label',
                    'explode' => false,
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ],
            ],
            '"style":"form" in "query"' => [
                new Cebe\Parameter([
                    'name' => 'id',
                    'in' => 'query',
                    'required' => false,
                    'style' => 'form',
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ]),
                [
                    'name' => 'id',
                    'in' => 'query',
                    'required' => false,
                    'style' => 'form',
                    'explode' => true,
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ],
            ],
            '"style":"simple" in "path"' => [
                new Cebe\Parameter([
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'style' => 'simple',
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ]),
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'style' => 'simple',
                    'explode' => false,
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ],
            ],
            '"style":"spaceDelimited" in "query"' => [
                new Cebe\Parameter([
                    'name' => 'id',
                    'in' => 'query',
                    'required' => false,
                    'style' => 'spaceDelimited',
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ]),
                [
                    'name' => 'id',
                    'in' => 'query',
                    'required' => false,
                    'style' => 'spaceDelimited',
                    'explode' => false,
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ],
            ],
            '"style":"pipeDelimited" in "query"' => [
                new Cebe\Parameter([
                    'name' => 'id',
                    'in' => 'query',
                    'required' => false,
                    'style' => 'pipeDelimited',
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ]),
                [
                    'name' => 'id',
                    'in' => 'query',
                    'required' => false,
                    'style' => 'pipeDelimited',
                    'explode' => false,
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ],
            ],
            '"style":"deepObject" in "query"' => [
                new Cebe\Parameter([
                    'name' => 'id',
                    'in' => 'query',
                    'required' => false,
                    'style' => 'deepObject',
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ]),
                [
                    'name' => 'id',
                    'in' => 'query',
                    'required' => false,
                    'style' => 'deepObject',
                    'explode' => false,
                    'schema' => new Cebe\Schema(['type' => 'integer']),
                ],
            ],
        ];
    }

    #[Test, TestDox('It will construct itself from valid Parameters')]
    #[DataProvider('provideValidParameters')]
    public function constructsAParameterSpecificationFromValidParameters(
        Cebe\Parameter $parameter,
        array $expectedProperties
    ): void {
        $sut = new Parameter($parameter);

        foreach ($expectedProperties as $key => $value) {
            self::assertEquals($value, $sut->$key, sprintf("'%s' doesn't match expected", $key));
        }
    }
}
