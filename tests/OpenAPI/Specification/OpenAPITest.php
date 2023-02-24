<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\Reader as CebeReader;
use cebe\openapi\spec as Cebe;
use Membrane\OpenAPI\Specification\OpenAPI;
use Membrane\OpenAPI\Specification\Operation;
use Membrane\OpenAPI\Specification\Path;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenAPI::class)]
#[UsesClass(Path::class)]
#[UsesClass(Operation::class)]
class OpenAPITest extends TestCase
{
    private Cebe\OpenApi $cebeOpenApi;
    /** @var array<string, Cebe\Server> */
    private array $rootServers;
    private OpenAPI $sut;

    protected function setUp(): void
    {
        $this->cebeOpenApi = CebeReader::readFromJson(
            <<<JSON
            {
              "openapi": "3.0.0",
              "info": {
                "version": "1.0.0",
                "title": "Test API"
              },
              "servers": [
                {
                  "url": "http://rootlevel.io"
                },
                {
                  "url": "http://rootlevel.net"
                }
              ],
              "paths": {
                  "/withoutpathserver": {
                  "get": {
                    "operationId": "without-path-server",
                    "responses": {
                      "200": {
                        "description": "Success"
                      }
                    }
                  }
                },
                "/withpathserver": {
                  "servers": [
                    {
                      "url": "http://pathlevel.io"
                    }
                  ],
                  "post": {
                    "operationId": "with-path-server",
                    "responses": {
                      "200": {
                        "description": "Success"
                      }
                    }
                  }
                }
              }
            }
            JSON,
            Cebe\OpenApi::class
        );

        $this->rootServers = [
            'http://rootlevel.io' => new Cebe\Server(['url' => 'http://rootlevel.io']),
            'http://rootlevel.net' => new Cebe\Server(['url' => 'http://rootlevel.net']),
        ];

        $this->sut = new OpenAPI($this->cebeOpenApi);
    }

    #[Test]
    #[TestDox('$servers contains all root-level servers with url keys')]
    public function serversContainsCebeServerObjectsWithURLKeys(): void
    {
        $expectedServers = $this->rootServers;

        self::assertEquals($expectedServers, $this->sut->servers);
    }

    #[Test]
    #[TestDox('$paths contains Path Specification values with relative-path keys for each path')]
    public function pathsContainsPathSpecificationValuesWithRelativePathKeys(): void
    {
        $expectedPaths = [
            '/withoutpathserver' => new Path(
                $this->cebeOpenApi->paths->getPath('/withoutpathserver'),
                ...$this->rootServers
            ),
            '/withpathserver' => new Path(
                $this->cebeOpenApi->paths->getPath('/withpathserver'),
                new Cebe\Server(['url' => 'http://pathlevel.io'])
            ),
        ];

        self::assertEquals($expectedPaths, $this->sut->paths);
    }
}
