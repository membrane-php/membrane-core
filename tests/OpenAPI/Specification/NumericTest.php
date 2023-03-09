<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\Numeric;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Numeric::class)]
#[CoversClass(APISchema::class)]
#[CoversClass(CannotProcessOpenAPI::class)]
class NumericTest extends TestCase
{
    #[Test]
    public function throwsExceptionForMissingType(): void
    {
        self::expectExceptionObject(
            CannotProcessOpenAPI::mismatchedType(Numeric::class, 'integer or number', 'no type')
        );

        new Numeric('', new Schema([]));
    }

    #[Test]
    public function throwsExceptionForInvalidType(): void
    {
        self::expectExceptionObject(
            CannotProcessOpenAPI::mismatchedType(Numeric::class, 'integer or number', 'string')
        );

        new Numeric('', new Schema(['type' => 'string']));
    }

    public static function dataSetsToConstruct(): array
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

    #[DataProvider('dataSetsToConstruct')]
    #[Test]
    public function constructTest(Schema $schema, array $expected): void
    {
        $sut = new Numeric('', $schema);

        foreach ($expected as $key => $value) {
            self::assertSame($value, $sut->$key, sprintf('%s did not meet expected value', $key));
        }
    }
}
