<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Exception;
use Membrane\OpenAPI\Specification\Numeric;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Specification\Numeric
 * @covers \Membrane\OpenAPI\Specification\APISchema
 */
class NumericTest extends TestCase
{
    /** @test */
    public function throwsExceptionForInvalidType(): void
    {
        self::expectExceptionObject(
            new Exception('Numeric Specification requires specified type of integer or number')
        );

        new Numeric('', new Schema([]));
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
