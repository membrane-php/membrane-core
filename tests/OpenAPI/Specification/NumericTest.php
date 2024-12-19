<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\Numeric;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30};
use Membrane\OpenAPIReader\ValueObject\Value;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Numeric::class)]
#[CoversClass(APISchema::class)]
#[CoversClass(CannotProcessSpecification::class)]
class NumericTest extends TestCase
{
    #[Test]
    public function throwsExceptionForMissingType(): void
    {
        self::expectExceptionObject(
            CannotProcessSpecification::mismatchedType(['integer', 'number'], []),
        );

        new Numeric(
            OpenAPIVersion::Version_3_0,
            '',
            (new V30\Schema(new Identifier('test'), new Partial\Schema()))->value,
        );
    }

    #[Test]
    public function throwsExceptionForInvalidType(): void
    {
        self::expectExceptionObject(
            CannotProcessSpecification::mismatchedType(['integer', 'number'], ['string']),
        );

        new Numeric(
            OpenAPIVersion::Version_3_0,
            '',
            (new V30\Schema(new Identifier('test'), new Partial\Schema(type: 'string')))->value,
        );
    }

    public static function dataSetsToConstruct(): array
    {
        return [
            'default values for number' => [
                OpenAPIVersion::Version_3_0,
                new V30\Schema(new Identifier('test'), new Partial\Schema(type: 'number')),
                [
                    'type' => 'number',
                    'maximum' => null,
                    'minimum' => null,
                    'exclusiveMaximum' => false,
                    'exclusiveMinimum' => false,
                    'multipleOf' => null,
                    'enum' => null,
                ],
            ],
            'default values for integer' => [
                OpenAPIVersion::Version_3_0,
                new V30\Schema(new Identifier('test'), new Partial\Schema(type: 'integer')),
                [
                    'type' => 'integer',
                    'maximum' => null,
                    'minimum' => null,
                    'exclusiveMaximum' => false,
                    'exclusiveMinimum' => false,
                    'multipleOf' => null,
                    'enum' => null,
                ],
            ],
            'assigned values for number' => [
                OpenAPIVersion::Version_3_0,
                new V30\Schema(new Identifier('test'), new Partial\Schema(
                    type: 'number',
                    enum: [new Value(3), new Value(9)],
                    multipleOf: 3,
                    exclusiveMaximum: true,
                    exclusiveMinimum: true,
                    maximum: 10,
                    minimum: 1,
                    format: 'float',
                )),
                [
                    'type' => 'number',
                    'maximum' => 10,
                    'minimum' => 1,
                    'exclusiveMaximum' => true,
                    'exclusiveMinimum' => true,
                    'multipleOf' => 3,
                    'enum' => [3, 9],
                    'format' => 'float',
                ],
            ],
            'assigned values for integer' => [
                OpenAPIVersion::Version_3_0,
                new V30\Schema(new Identifier('test'), new Partial\Schema(
                    type: 'integer',
                    enum: [new Value(9)],
                    multipleOf: 3,
                    exclusiveMaximum: true,
                    exclusiveMinimum: true,
                    maximum: 10,
                    minimum: 1,
                    format: 'square of 3',
                )),
                [
                    'type' => 'integer',
                    'maximum' => 10,
                    'minimum' => 1,
                    'exclusiveMaximum' => true,
                    'exclusiveMinimum' => true,
                    'multipleOf' => 3,
                    'enum' => [9],
                    'format' => 'square of 3',
                ],
            ],
        ];
    }

    #[DataProvider('dataSetsToConstruct')]
    #[Test]
    public function constructTest(
        OpenAPIVersion $openAPIVersion,
        V30\Schema $schema,
        array $expected
    ): void {
        $sut = new Numeric($openAPIVersion, '', $schema->value);

        foreach ($expected as $key => $value) {
            self::assertSame($value, $sut->$key, sprintf('%s did not meet expected value', $key));
        }
    }
}
