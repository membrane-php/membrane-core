<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Specification\Numeric;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Specification\Numeric
 * @covers \Membrane\OpenAPI\Specification\APISchema
 * @covers \Membrane\OpenAPI\Exception\CannotProcessOpenAPI
 */
class NumericTest extends TestCase
{
    /** @test */
    public function throwsExceptionForMissingType(): void
    {
        self::expectExceptionObject(
            CannotProcessOpenAPI::mismatchedType(Numeric::class, 'integer or number', 'no type')
        );

        new Numeric('', new Schema([]));
    }

    /** @test */
    public function throwsExceptionForInvalidType(): void
    {
        self::expectExceptionObject(
            CannotProcessOpenAPI::mismatchedType(Numeric::class, 'integer or number', 'string')
        );

        new Numeric('', new Schema(['type' => 'string']));
    }

    public function dataSetsToConstruct(): array
    {
        return [
            'default values for number' => [
                new Schema(['type' => 'number',]),
                [
                    'type' => 'number',
                    'maximum' => null,
                    'minimum' => null,
                    'exclusiveMaximum' => false,
                    'exclusiveMinimum' => false,
                    'multipleOf' => null,
                    'enum' => null,
                    'nullable' => false,
                ],
            ],
            'default values for integer' => [
                new Schema(['type' => 'integer',]),
                [
                    'type' => 'integer',
                    'maximum' => null,
                    'minimum' => null,
                    'exclusiveMaximum' => false,
                    'exclusiveMinimum' => false,
                    'multipleOf' => null,
                    'enum' => null,
                    'nullable' => false,
                ],
            ],
            'assigned values for number' => [
                new Schema([
                    'type' => 'number',
                    'maximum' => 10,
                    'minimum' => 1,
                    'exclusiveMaximum' => true,
                    'exclusiveMinimum' => true,
                    'multipleOf' => 3,
                    'enum' => [3, 9],
                    'format' => 'float',
                    'nullable' => true,
                ]),
                [
                    'type' => 'number',
                    'maximum' => 10,
                    'minimum' => 1,
                    'exclusiveMaximum' => true,
                    'exclusiveMinimum' => true,
                    'multipleOf' => 3,
                    'enum' => [3, 9],
                    'format' => 'float',
                    'nullable' => true,
                ],
            ],
            'assigned values for integer' => [
                new Schema([
                    'type' => 'integer',
                    'maximum' => 10,
                    'minimum' => 1,
                    'exclusiveMaximum' => true,
                    'exclusiveMinimum' => true,
                    'multipleOf' => 3,
                    'enum' => [3, 9],
                    'format' => 'square numbers',
                    'nullable' => true,
                ]),
                [
                    'type' => 'integer',
                    'maximum' => 10,
                    'minimum' => 1,
                    'exclusiveMaximum' => true,
                    'exclusiveMinimum' => true,
                    'multipleOf' => 3,
                    'enum' => [3, 9],
                    'format' => 'square numbers',
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
        $sut = new Numeric('', $schema);

        foreach ($expected as $key => $value) {
            self::assertSame($value, $sut->$key, sprintf('%s did not meet expected value', $key));
        }
    }
}
