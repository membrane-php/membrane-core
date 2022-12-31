<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Specification\Objects;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Specification\Objects
 * @covers \Membrane\OpenAPI\Specification\APISchema
 * @covers \Membrane\OpenAPI\Exception\CannotProcessOpenAPI
 */
class ObjectsTest extends TestCase
{
    /** @test */
    public function throwsExceptionForMissingType(): void
    {
        self::expectExceptionObject(CannotProcessOpenAPI::mismatchedType(Objects::class, 'object', 'no type'));

        new Objects('', new Schema([]));
    }

    /** @test */
    public function throwsExceptionForIncorrectType(): void
    {
        self::expectExceptionObject(CannotProcessOpenAPI::mismatchedType(Objects::class, 'object', 'string'));

        new Objects('', new Schema(['type' => 'string']));
    }

    public function dataSetsToConstruct(): array
    {
        return [
            'default values' => [
                new Schema(['type' => 'object',]),
                [
                    'properties' => [],
                    'required' => null,
                    'enum' => null,
                    'format' => null,
                    'nullable' => false,
                ],
            ],
            'assigned values' => [
                new Schema([
                    'type' => 'object',
                    'properties' => ['id' => new Schema(['type' => 'integer'])],
                    'required' => ['id'],
                    'enum' => [false, null],
                    'format' => 'you cannot say yes',
                    'nullable' => true,
                ]),
                [
                    'properties' => ['id' => new Schema(['type' => 'integer'])],
                    'required' => ['id'],
                    'enum' => [false, null],
                    'format' => 'you cannot say yes',
                    'nullable' => true,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToConstruct
     */
    public function constructTest(Schema $schema, array $expected): void
    {
        $sut = new Objects('', $schema);

        foreach ($expected as $key => $value) {
            if ($key === 'properties') {
                self::assertEquals($value, $sut->$key, sprintf('%s does not meet expected value', $key));
            } else {
                self::assertSame($value, $sut->$key, sprintf('%s does not meet expected value', $key));
            }
        }
    }
}
