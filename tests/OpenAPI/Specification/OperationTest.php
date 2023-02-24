<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\Reader as CebeReader;
use cebe\openapi\spec as Cebe;
use Membrane\OpenAPI\Specification\Operation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(Operation::class)]
class OperationTest extends TestCase
{
    /** @var array<string, Cebe\Server> */
    private array $pathServers;
    /** @var array<string, Cebe\Parameter> */
    private array $pathParameters;

    protected function setUp(): void
    {
        $this->pathServers = ['http://path.net' => new Cebe\Server(['url' => 'http://path.net'])];

        $this->pathParameters = [
            'id' => new Cebe\Parameter(
                ['name' => 'id', 'in' => 'query', 'schema' => new Cebe\Schema(['type' => 'integer'])]
            ),
            'name' => new Cebe\Parameter(
                ['name' => 'name', 'in' => 'path', 'schema' => new Cebe\Schema(['type' => 'string'])]
            ),
        ];
    }

    public static function provideOperationsWithOrWithoutParameters(): array
    {
        return [
            'operation without parameters will contain only path parameters' => [
                CebeReader::readFromJson(
                    <<<JSON
                  {
                    "operationId": "with-operation-server",
                    "responses": {
                      "200": {
                        "description": "Success"
                      }
                    }
                  }
                  JSON,
                    Cebe\Operation::class
                ),
                [
                    'id' => new Cebe\Parameter(
                        ['name' => 'id', 'in' => 'query', 'schema' => new Cebe\Schema(['type' => 'integer'])]
                    ),
                    'name' => new Cebe\Parameter(
                        ['name' => 'name', 'in' => 'path', 'schema' => new Cebe\Schema(['type' => 'string'])]
                    ),
                ],
            ],
            'operation with parameters will contain operation parameters and any differently named path parameters' => [
                CebeReader::readFromJson(
                    <<<JSON
                  {
                    "operationId": "with-operation-server",
                      "parameters": [
                        {
                          "name": "id",
                          "in": "path",
                          "schema": {
                            "type": "float"
                          }
                        },
                        {
                          "name": "species",
                          "in": "header",
                          "schema": {
                            "type": "string"
                          }
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
                [
                    'id' => new Cebe\Parameter(
                        ['name' => 'id', 'in' => 'path', 'schema' => new Cebe\Schema(['type' => 'float'])]
                    ),
                    'name' => new Cebe\Parameter(
                        ['name' => 'name', 'in' => 'path', 'schema' => new Cebe\Schema(['type' => 'string'])]
                    ),
                    'species' => new Cebe\Parameter(
                        ['name' => 'species', 'in' => 'header', 'schema' => new Cebe\Schema(['type' => 'string'])]
                    ),
                ],
            ],
        ];
    }

    #[Test]
    #[TestDox('operation-level parameters will override path-level parameters of the same name (OpenAPI Compliant)')]
    #[DataProvider('provideOperationsWithOrWithoutParameters')]
    public function operationParametersOverridePathParameters(
        Cebe\Operation $operation,
        array $expectedParameters
    ): void {
        $sut = new Operation($operation, $this->pathParameters, ...$this->pathServers);

        self::assertEquals($expectedParameters, $sut->parameters);
    }

    public static function provideOperationsWithOrWithoutServers(): array
    {
        return [
            'operation without servers' => [
                CebeReader::readFromJson(
                    <<<JSON
                  {
                    "operationId": "without-server",
                    "responses": {
                      "200": {
                        "description": "Success"
                      }
                    }
                  }
                  JSON,
                    Cebe\Operation::class
                ),
                ['http://path.net' => new Cebe\Server(['url' => 'http://path.net'])],
            ],
            'operation with servers' => [
                CebeReader::readFromJson(
                    <<<JSON
                  {
                    "operationId": "with-server",
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
                ['http://operation.social' => new Cebe\Server(['url' => 'http://operation.social'])],
            ],
        ];
    }

    #[Test]
    #[TestDox('operation-level servers will override both path-level and root-level servers (OpenAPI Compliant)')]
    #[DataProvider('provideOperationsWithOrWithoutServers')]
    public function operationServersOverridePathAndRootServers(
        Cebe\Operation $operation,
        array $expectedServers
    ): void {
        $sut = new Operation($operation, $this->pathParameters, ...$this->pathServers);

        self::assertEquals($expectedServers, $sut->servers);
    }

}
