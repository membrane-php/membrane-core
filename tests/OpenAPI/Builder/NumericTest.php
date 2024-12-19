<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder;

use Membrane\Filter\Type\ToFloat;
use Membrane\Filter\Type\ToInt;
use Membrane\Filter\Type\ToNumber;
use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\Numeric;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Specification;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30};
use Membrane\OpenAPIReader\ValueObject\Value;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\Numeric\Maximum;
use Membrane\Validator\Numeric\Minimum;
use Membrane\Validator\Numeric\MultipleOf;
use Membrane\Validator\String\IntString;
use Membrane\Validator\String\NumericString;
use Membrane\Validator\Type\IsFloat;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsNull;
use Membrane\Validator\Type\IsNumber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Numeric::class)]
#[CoversClass(APIBuilder::class)]
#[UsesClass(AnyOf::class)]
#[UsesClass(Field::class)]
#[UsesClass(Contained::class)]
#[UsesClass(Maximum::class)]
#[UsesClass(Minimum::class)]
#[UsesClass(MultipleOf::class)]
class NumericTest extends TestCase
{
    #[Test]
    public function supportsNumericSpecification(): void
    {
        $specification = self::createStub(Specification\Numeric::class);
        $sut = new Numeric();

        self::assertTrue($sut->supports($specification));
    }

    #[Test]
    public function doesNotSupportSpecificationsOtherThanNumeric(): void
    {
        $specification = self::createStub(\Membrane\Builder\Specification::class);
        $sut = new Numeric();

        self::assertFalse($sut->supports($specification));
    }

    public static function specificationsToBuild(): array
    {
        return [
            'integer input to convert from string' => [
                new Specification\Numeric(
                    OpenAPIVersion::Version_3_0,
                    '',
                    (new V30\Schema(new Identifier(''), new Partial\Schema(type: 'integer')))->value,
                    true
                ),
                new Field('', new IntString(), new ToInt()),
            ],
            'strict integer input' => [
                new Specification\Numeric(
                    OpenAPIVersion::Version_3_0,
                    '',
                    (new V30\Schema(new Identifier(''), new Partial\Schema(type: 'integer')))->value,
                    false,
                ),
                new Field('', new IsInt()),
            ],
            'number input to convert from string' => [
                new Specification\Numeric(
                    OpenAPIVersion::Version_3_0,
                    '',
                    (new V30\Schema(new Identifier(''), new Partial\Schema(type: 'number')))->value,
                    true,
                ),
                new Field('', new NumericString(), new ToNumber()),
            ],
            'strict number input' => [
                new Specification\Numeric(
                    OpenAPIVersion::Version_3_0,
                    '',
                    (new V30\Schema(new Identifier(''), new Partial\Schema(type: 'number')))->value,
                    false,
                ),
                new Field('', new IsNumber()),
            ],
            'float input to convert from string' => [
                new Specification\Numeric(
                    OpenAPIVersion::Version_3_0,
                    '',
                    (new V30\Schema(new Identifier(''), new Partial\Schema(type: 'number', format: 'float')))->value,
                    true,
                ),
                new Field('', new NumericString(), new ToFloat()),
            ],
            'strict float input' => [
                new Specification\Numeric(
                    OpenAPIVersion::Version_3_0,
                    '',
                    (new V30\Schema(new Identifier(''), new Partial\Schema(type: 'number', format: 'float')))->value,
                    false,
                ),
                new Field('', new IsFloat()),
            ],
            'detailed input to convert from string' => [
                new Specification\Numeric(
                    OpenAPIVersion::Version_3_0,
                    '',
                    (new V30\Schema(new Identifier(''), new Partial\Schema(
                        type: 'integer',
                        enum: [new Value(1), new Value(2), new Value(3), new Value(null)],
                        multipleOf: 3,
                        exclusiveMaximum: true,
                        exclusiveMinimum: true,
                        maximum: 4,
                        minimum: 0,
                        format: 'int',
                    )))->value,
                    true
                ),
                new Field(
                    '',
                    new IntString(),
                    new ToInt(),
                    new Contained([1, 2, 3, null]),
                    new Maximum(4, true),
                    new Minimum(0, true),
                    new MultipleOf(3)
                )
            ],
            'strict detailed input' => [
                new Specification\Numeric(
                    OpenAPIVersion::Version_3_0,
                    '',
                    (new V30\Schema(new Identifier(''), new Partial\Schema(
                        type: 'integer',
                        enum: [new Value(1), new Value(2), new Value(3), new Value(null)],
                        multipleOf: 3,
                        exclusiveMaximum: true,
                        exclusiveMinimum: true,
                        maximum: 4,
                        minimum: 0,
                        format: 'int',
                    )))->value,
                    false
                ),
                new Field(
                    '',
                    new IsInt(),
                    new Contained([1, 2, 3, null]),
                    new Maximum(4, true),
                    new Minimum(0, true),
                    new MultipleOf(3)
                )
            ],
        ];
    }

    #[DataProvider('specificationsToBuild')]
    #[Test]
    public function buildTest(Specification\Numeric $specification, Processor $expected): void
    {
        $sut = new Numeric();

        $actual = $sut->build($specification);

        self::assertEquals($expected, $actual);
    }
}
