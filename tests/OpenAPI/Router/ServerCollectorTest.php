<?php

declare(strict_types=1);

namespace OpenAPI\Router;

use cebe\openapi\Reader;
use Membrane\OpenAPI\Router\ServerCollector;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Router\ServerCollector
 * @uses   \Membrane\OpenAPI\Reader\OpenAPIFileReader
 */
class ServerCollectorTest extends TestCase
{
    public const FIXTURES = __DIR__ . '/../../fixtures/OpenAPI/';

    public function collectTestProvider(): array
    {
        return [
            'simple.json' => [
                ['operationIds' => [[]], 'servers' => ['/(*MARK:0)']],
                self::FIXTURES . 'simple.json',
            ],
            'petstore.yaml' => [
                [
                    'operationIds' => [['findPets', 'addPet', 'find pet by id', 'deletePet']],
                    'servers' => ['http://petstore.swagger.io/api(*MARK:0)'],
                ],
                self::FIXTURES . 'docs/petstore-expanded.json',
            ],
            'WeirdAndWonderful.json' => [
                [
                    'operationIds' => [
                        ['get-and'],
                        ['put-and', 'post-and'],
                        ['post-or', 'delete-xor'],
                        ['post-or', 'delete-xor'],
                    ],
                    'servers' => [
                        'http://weirdest.com(*MARK:0)',
                        'http://weirder.co.uk(*MARK:1)',
                        'http://wonderful.io(*MARK:2)',
                        'http://weird.io/([^/]+)(*MARK:3)',
                    ],
                ],
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
    public function collectTest(array $expected, string $apiFilePath): void
    {
        $openApi = Reader::readFromJsonFile($apiFilePath);
        $sut = new ServerCollector();

        $actual = $sut->collect($openApi);

        self::assertSame($expected, $actual);
    }
}
