<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Specification\Arrays;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Specification\Arrays
 * @covers \Membrane\OpenAPI\Specification\APISchema
 * @covers \Membrane\OpenAPI\Exception\CannotProcessOpenAPI
 */
class ArraysTest extends TestCase
{
    /** @test */
    public function throwsExceptionForMissingType(): void
    {
        self::expectExceptionObject(CannotProcessOpenAPI::mismatchedType(Arrays::class, 'array', 'no type'));

        new Arrays('', new Schema([]));
    }

    /** @test */
    public function throwsExceptionForInvalidType(): void
    {
        self::expectExceptionObject(CannotProcessOpenAPI::mismatchedType(Arrays::class, 'array', 'string'));

        new Arrays('', new Schema(['type' => 'string']));
    }

    public function dataSetsToConstruct(): array
    {
        return [
            'default values' => [
                new Schema(['type' => 'array',]),
                [
                    'items' => null,
                    'maxItems' => null,
                    'minItems' => 0,
                    'uniqueItems' => false,
                    'enum' => null,
                    'format' => null,
                    'nullable' => false,
                ],
            ],
            'assigned values' => [
                new Schema([
                    'type' => 'array',
                    'items' => new Schema(['type' => 'integer']),
                    'maxItems' => 5,
                    'minItems' => 2,
                    'uniqueItems' => true,
                    'enum' => [[1, 2, 3], [5, 6, 7]],
                    'format' => 'array of ints',
                    'nullable' => true,
                ]),
                [
                    'items' => new Schema(['type' => 'integer']),
                    'maxItems' => 5,
                    'minItems' => 2,
                    'uniqueItems' => true,
                    'enum' => [[1, 2, 3], [5, 6, 7]],
                    'format' => 'array of ints',
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
        $sut = new Arrays('', $schema);

        foreach ($expected as $key => $value) {
            if ($key === 'items') {
                self::assertEquals($value, $sut->$key, sprintf('%s does not meet expected value', $key));
            } else {
                self::assertSame($value, $sut->$key, sprintf('%s does not meet expected value', $key));
            }
        }
    }
}
