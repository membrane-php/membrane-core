<?php

declare(strict_types=1);

namespace OpenAPI\Router;

use cebe\openapi\Reader;
use Membrane\OpenAPI\Router\PathCollector;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Router\PathCollector
 * @uses   \Membrane\OpenAPI\Reader\OpenAPIFileReader
 */
class PathCollectorTest extends TestCase
{
    public const FIXTURES = __DIR__ . '/../../fixtures/OpenAPI/';

    public function collectTestProvider(): array
    {
        return [
            'simple.json' => [
                ['operationIds' => [[]], 'paths' => ['/path(*MARK:0)']],
                self::FIXTURES . 'simple.json',
            ],
            'petstore.yaml' => [
                [
                    'operationIds' => [
                        ['get' => 'findPets', 'post' => 'addPet'],
                        ['get' => 'find pet by id', 'delete' => 'deletePet'],
                    ],
                    'paths' => ['/pets(*MARK:0)', '/pets/([^/]+)(*MARK:1)'],
                ],
                self::FIXTURES . 'docs/petstore-expanded.json',
            ],
            'WeirdAndWonderful.json' => [
                [
                    'operationIds' => [
                        ['get' => 'get-and', 'put' => 'put-and', 'post' => 'post-and',],
                        ['post' => 'post-or'],
                        ['delete' => 'delete-xor'],
                    ],
                    'paths' => [
                        '/and(*MARK:0)',
                        '/or(*MARK:1)',
                        '/xor(*MARK:2)',
                    ],
                ],
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
    public function collectTest(array $expected, string $apiFilePath): void
    {
        $openApi = Reader::readFromJsonFile($apiFilePath);
        $sut = new PathCollector();

        $actual = $sut->collect($openApi);

        self::assertSame($expected, $actual);
    }
}
