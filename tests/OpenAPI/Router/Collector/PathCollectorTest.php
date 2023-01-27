<?php

declare(strict_types=1);

namespace OpenAPI\Router\Collector;

use cebe\openapi\Reader;
use Membrane\OpenAPI\Router\Collection\PathCollection;
use Membrane\OpenAPI\Router\Collector\PathCollector;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Router\Collector\PathCollector
 * @uses   \Membrane\OpenAPI\Reader\OpenAPIFileReader
 * @uses   \Membrane\OpenAPI\Router\Collection\PathCollection
 */
class PathCollectorTest extends TestCase
{
    public const FIXTURES = __DIR__ . '/../../../fixtures/OpenAPI/';

    public function collectTestProvider(): array
    {
        return [
            'simple.json' => [
                new PathCollection(operationIds: [[]], paths: ['/path(*MARK:0)']),
                self::FIXTURES . 'simple.json',
            ],
            'petstore.yaml' => [
                new PathCollection(
                    operationIds: [
                        ['get' => 'findPets', 'post' => 'addPet'],
                        ['get' => 'find pet by id', 'delete' => 'deletePet'],
                    ],
                    paths: [
                        '/pets(*MARK:0)',
                        '/pets/([^/]+)(*MARK:1)',
                    ],
                ),
                self::FIXTURES . 'docs/petstore-expanded.json',
            ],
            'WeirdAndWonderful.json' => [
                new PathCollection(
                    operationIds: [
                        ['get' => 'get-and', 'put' => 'put-and', 'post' => 'post-and',],
                        ['post' => 'post-or'],
                        ['delete' => 'delete-xor'],
                        ['get' => 'get-however', 'put' => 'put-however', 'post' => 'post-however'],
                    ],
                    paths: [
                        '/and(*MARK:0)',
                        '/or(*MARK:1)',
                        '/xor(*MARK:2)',
                        '/however(*MARK:3)',
                    ]
                ),
                self::FIXTURES . 'WeirdAndWonderful.json',
            ],
        ];
    }

    /**
     * Tests it can collect methods => operationIds with an index matching the regex capturing group
     *
     * @test
     * @dataProvider collectTestProvider
     */
    public function collectTest(PathCollection $expected, string $apiFilePath): void
    {
        $openApi = Reader::readFromJsonFile($apiFilePath);
        $sut = new PathCollector();

        $actual = $sut->collect($openApi);

        self::assertEquals($expected, $actual);
    }
}
