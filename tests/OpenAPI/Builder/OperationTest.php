<?php

declare(strict_types=1);

namespace OpenAPI\Builder;

use cebe\openapi\Reader as CebeReader;
use cebe\openapi\spec as Cebe;
use Membrane\OpenAPI\Builder as Builder;
use Membrane\OpenAPI\Specification as Specification;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Builder\Operation::class)]
#[UsesClass(Builder\Numeric::class)]
#[UsesClass(Builder\Strings::class)]
#[UsesClass(Specification\APISchema::class)]
#[UsesClass(Specification\Numeric::class)]
#[UsesClass(Specification\Strings::class)]
#[UsesClass(BeforeSet::class)]
#[UsesClass(Field::class)]
#[UsesClass(FieldSet::class)]
#[UsesClass(RequiredFields::class)]
class OperationTest extends TestCase
{
    public static function provideOperationSpecifications(): array
    {
        return [
            'Without parameters' => [
                new Specification\Operation(
                    CebeReader::readFromJson(
                        <<<JSON
                        {
                          "operationId": "operation-without-parameters",
                          "servers": [
                            {
                              "url": "http://operation.social"
                            }
                          ],
                          "responses": {
                            "200": {
                              "description": "Success"
                            }
                          }
                        }
                        JSON,
                        Cebe\Operation::class
                    ),
                    [],
                ),
                new FieldSet('operation-without-parameters'),
            ],
            'Two parameters "in" same location, both required (path parameters are required by default)' => [
                new Specification\Operation(
                    CebeReader::readFromJson(
                        <<<JSON
                        {
                          "operationId": "operation-with-two-parameters",
                          "parameters": [
                            {
                              "name": "id",
                              "in": "path",
                              "schema": {
                                "type": "integer"
                              }
                            },
                            {
                              "name": "species",
                              "in": "path",
                              "schema": {
                                "type": "string"
                              }
                            }
                          ],
                          "servers": [
                            {
                              "url": "http://operation.social"
                            }
                          ],
                          "responses": {
                            "200": {
                              "description": "Success"
                            }
                          }
                        }
                        JSON,
                        Cebe\Operation::class
                    ),
                    [],
                ),
                new FieldSet(
                    'operation-with-two-parameters',
                    new FieldSet(
                        'path',
                        new BeforeSet(new RequiredFields('id', 'species')),
                        new Field('id', new IsInt()),
                        new Field('species', new IsString())
                    )
                ),
            ],
            'Two parameters "in" same location, both required (query parameters are not required by default)' => [
                new Specification\Operation(
                    CebeReader::readFromJson(
                        <<<JSON
                        {
                          "operationId": "operation-with-two-parameters",
                          "parameters": [
                            {
                              "name": "id",
                              "in": "query",
                              "required": true,
                              "schema": {
                                "type": "integer"
                              }
                            },
                            {
                              "name": "species",
                              "in": "query",
                              "required": true,
                              "schema": {
                                "type": "string"
                              }
                            }
                          ],
                          "servers": [
                            {
                              "url": "http://operation.social"
                            }
                          ],
                          "responses": {
                            "200": {
                              "description": "Success"
                            }
                          }
                        }
                        JSON,
                        Cebe\Operation::class
                    ),
                    [],
                ),
                new FieldSet(
                    'operation-with-two-parameters',
                    new FieldSet(
                        'query',
                        new BeforeSet(new RequiredFields('id', 'species')),
                        new Field('id', new IsInt()),
                        new Field('species', new IsString())
                    )
                ),
            ],
            'Two parameters "in" different locations, both required' => [
                new Specification\Operation(
                    CebeReader::readFromJson(
                        <<<JSON
                        {
                          "operationId": "operation-with-two-parameters",
                          "parameters": [
                            {
                              "name": "id",
                              "in": "cookie",
                              "required": true,
                              "schema": {
                                "type": "integer"
                              }
                            },
                            {
                              "name": "species",
                              "in": "header",
                              "required": true,
                              "schema": {
                                "type": "string"
                              }
                            }
                          ],
                          "servers": [
                            {
                              "url": "http://operation.social"
                            }
                          ],
                          "responses": {
                            "200": {
                              "description": "Success"
                            }
                          }
                        }
                        JSON,
                        Cebe\Operation::class
                    ),
                    [],
                ),
                new FieldSet(
                    'operation-with-two-parameters',
                    new FieldSet(
                        'cookie',
                        new BeforeSet(new RequiredFields('id')),
                        new Field('id', new IsInt())
                    ),
                    new FieldSet(
                        'header',
                        new BeforeSet(new RequiredFields('species')),
                        new Field('species', new IsString())
                    )
                ),
            ],
        ];
    }

    #[Test]
    #[TestDox('Builds the relevant processor for the given Operation Specification')]
    #[DataProvider('provideOperationSpecifications')]
    public function buildsOperationProcessor(Specification\Operation $specification, Processor $expectedProcessor): void
    {
        $sut = new Builder\Operation();

        $actualProcessor = $sut->build($specification);

        self::assertEquals($expectedProcessor, $actualProcessor);
    }

}
