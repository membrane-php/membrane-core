<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\Reader as CebeReader;
use cebe\openapi\spec as Cebe;
use Membrane\OpenAPI\Specification\Operation;
use Membrane\OpenAPI\Specification\Path;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Path::class)]
#[UsesClass(Operation::class)]
class PathTest extends TestCase
{
    /** @var array<string, Cebe\Server> */
    private array $rootServers;

    protected function setUp(): void
    {
        $this->rootServer = ['http://root.io' => new Cebe\Server(['url' => 'http://root.io'])];
    }

    public static function providePathsWithOrWithoutServers(): array
    {
        return [
            'path without path servers' => [
                CebeReader::readFromJson(
                    <<<JSON
                    {
                      "get": {
                        "operationId": "without--server",
                        "responses": {
                          "200": {
                            "description": "Success"
                          }
                        }
                      }
                    }
                    JSON,
                    Cebe\PathItem::class
                ),
                ['http://root.io' => new Cebe\Server(['url' => 'http://root.io'])],
            ],
            'path with path servers' => [
                CebeReader::readFromJson(
                    <<<JSON
                  {
                    "servers": [
                      {
                        "url": "http://path.net"
                      }
                    ],
                    "post": {
                      "operationId": "withS--server",
                      "responses": {
                        "200": {
                          "description": "Success"
                        }
                      }
                    }
                  }
                  JSON,
                    Cebe\PathItem::class
                ),
                ['http://path.net' => new Cebe\Server(['url' => 'http://path.net'])],
            ],
        ];
    }

    #[Test]
    #[TestDox('path-level servers should override root-level servers if specified (OpenAPI compliant)')]
    #[DataProvider('providePathsWithOrWithoutServers')]
    public function pathServersWillOverrideRootServersIfSpecified(
        Cebe\PathItem $pathItem,
        array $expectedServers
    ): void {
        $sut = new Path($pathItem, ...$this->rootServer);

        $actualServers = $sut->servers;

        self::assertEquals($expectedServers, $actualServers);
    }

    public static function providePathsWithOrWithoutParameters(): array
    {
        return [
            'path without path paramters' => [
                CebeReader::readFromJson(
                    <<<JSON
                    {
                      "put": {
                        "operationId": "without-parameters",
                        "responses": {
                          "200": {
                            "description": "Success"
                          }
                        }
                      }
                    }
                    JSON,
                    Cebe\PathItem::class
                ),
                [],
            ],
            'path with path parameters' => [
                CebeReader::readFromJson(
                    <<<JSON
                    {
                      "parameters": [
                        {
                          "name": "id",
                          "in": "query",
                          "schema": {
                            "type": "integer"
                          }
                        }
                      ],
                      "delete": {
                        "operationId": "with-parameter",
                        "responses": {
                          "200": {
                            "description": "Success"
                          }
                        }
                      }
                    }
                    JSON,
                    Cebe\PathItem::class
                ),
                [
                    'id' => new Cebe\Parameter(
                        ['name' => 'id', 'in' => 'query', 'schema' => new Cebe\Schema(['type' => 'integer'])]
                    ),
                ],
            ],
        ];
    }

    #[Test]
    #[TestDox('$parameters contains cebe\openapi\Parameter object values with name keys for each path parameter')]
    #[DataProvider('providePathsWithOrWithoutParameters')]
    public function parametersContainsParametersWithNameKeys(Cebe\PathItem $pathItem, array $expectedParameters): void
    {
        $sut = new Path($pathItem, ...$this->rootServer);

        self::assertEquals($expectedParameters, $sut->parameters);
    }

    public static function providePathsWithOperationsWithOrWithoutServers(): array
    {
        return [
            'path without path servers without operation servers' => [
                CebeReader::readFromJson(
                    <<<JSON
                    {
                      "get": {
                        "operationId": "without-path-server-without-operation-server",
                        "responses": {
                          "200": {
                            "description": "Success"
                          }
                        }
                      }
                    }
                    JSON,
                    Cebe\PathItem::class
                ),
                'get',
                ['http://root.io' => new Cebe\Server(['url' => 'http://root.io'])],
            ],
            'path without path servers with operation servers' => [
                CebeReader::readFromJson(
                    <<<JSON
                    {
                      "put": {
                        "operationId": "without-path-server-with-operation-server",
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
                    }
                    JSON,
                    Cebe\PathItem::class
                ),
                'put',
                ['http://operation.social' => new Cebe\Server(['url' => 'http://operation.social'])],
            ],
            'path with path servers without operation servers' => [
                CebeReader::readFromJson(
                    <<<JSON
                  {
                    "servers": [
                      {
                        "url": "http://path.net"
                      }
                    ],
                    "post": {
                      "operationId": "without-operation-server",
                      "responses": {
                        "200": {
                          "description": "Success"
                        }
                      }
                    }
                  }
                  JSON,
                    Cebe\PathItem::class
                ),
                'post',
                ['http://path.net' => new Cebe\Server(['url' => 'http://path.net'])],
            ],
            'path with path servers with operation servers' => [
                CebeReader::readFromJson(
                    <<<JSON
                  {
                    "servers": [
                      {
                        "url": "http://path.net"
                      }
                    ],
                    "delete": {
                      "operationId": "with-operation-server",
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
                  }
                  JSON,
                    Cebe\PathItem::class
                ),
                'delete',
                ['http://operation.social' => new Cebe\Server(['url' => 'http://operation.social'])],
            ],
        ];
    }

    #[Test]
    #[TestDox('$operations contains Operation Specification values with corresponding method keys for each operation')]
    #[DataProvider('providePathsWithOperationsWithOrWithoutServers')]
    public function operationsContainsOperationSpecificationsValuesWithMethodsKeys(
        Cebe\PathItem $pathItem,
        string $method,
        array $expectedOperationServers
    ): void {
        $sut = new Path($pathItem, ...$this->rootServer);

        $actualOperationServers = $sut->operations[$method]->servers;

        self::assertEquals($expectedOperationServers, $actualOperationServers);
    }
}
