<?php

declare(strict_types=1);

namespace OpenAPI\Router\Collector;

use cebe\openapi\Reader;
use Membrane\OpenAPI\Router\Collection\ServerCollection;
use Membrane\OpenAPI\Router\Collector\ServerCollector;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Router\Collector\ServerCollector
 * @uses   \Membrane\OpenAPI\Reader\OpenAPIFileReader
 * @uses   \Membrane\OpenAPI\Router\Collection\ServerCollection
 */
class ServerCollectorTest extends TestCase
{
    public const FIXTURES = __DIR__ . '/../../../fixtures/OpenAPI/';

    public function collectTestProvider(): array
    {
        return [
            'simple.json' => [
                new ServerCollection(operationIds: [], servers: []),
                self::FIXTURES . 'simple.json',
            ],
            'petstore.yaml' => [
                new ServerCollection(
                    operationIds: [['findPets', 'addPet', 'find pet by id', 'deletePet']],
                    servers: ['http://petstore.swagger.io/api(*MARK:0)']
                ),
                self::FIXTURES . 'docs/petstore-expanded.json',
            ],
            'WeirdAndWonderful.json' => [
                new ServerCollection(
                    operationIds: [
                        ['get-and', 'put-however', 'post-however'],
                        ['put-and', 'post-and', 'get-however'],
                        ['post-or', 'delete-xor'],
                        ['post-or', 'delete-xor'],
                    ],
                    servers: [
                        'http://weirdest.com(*MARK:0)',
                        'http://weirder.co.uk(*MARK:1)',
                        'http://wonderful.io(*MARK:2)',
                        'http://weird.io/([^/]+)(*MARK:3)',
                    ]
                ),
                self::FIXTURES . 'WeirdAndWonderful.json',
            ],
        ];
    }

    /**
     * Tests it can collect operationIds with an index matching the regex capturing group
     *
     * @test
     * @dataProvider collectTestProvider
     */
    public function collectTest(ServerCollection $expected, string $apiFilePath): void
    {
        $openApi = Reader::readFromJsonFile($apiFilePath);
        $sut = new ServerCollector();

        $actual = $sut->collect($openApi);

        self::assertEquals($expected, $actual);
    }
}
