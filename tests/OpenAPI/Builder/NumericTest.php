<?php

declare(strict_types=1);

namespace OpenAPI\Builder;

use cebe\openapi\spec\Schema;
use Membrane\Filter\Type\ToFloat;
use Membrane\Filter\Type\ToInt;
use Membrane\Filter\Type\ToNumber;
use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\Numeric;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Specification;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\Numeric\Maximum;
use Membrane\Validator\Numeric\Minimum;
use Membrane\Validator\Numeric\MultipleOf;
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
            'non-strict integer input' => [
                new Specification\Numeric('', new Schema(['type' => 'integer']), false),
                new Field('', new ToInt(), new IsInt()),
            ],
            'strict integer input' => [
                new Specification\Numeric('', new Schema(['type' => 'integer']), true),
                new Field('', new IsInt()),
            ],
            'non-strict number input' => [
                new Specification\Numeric('', new Schema(['type' => 'number']), false),
                new Field('', new ToNumber(), new IsNumber()),
            ],
            'strict number input' => [
                new Specification\Numeric('', new Schema(['type' => 'number']), true),
                new Field('', new IsNumber()),
            ],
            'non-strict float input' => [
                new Specification\Numeric('', new Schema(['type' => 'number', 'format' => 'float']), false),
                new Field('', new ToFloat(), new IsFloat()),
            ],
            'strict float input' => [
                new Specification\Numeric('', new Schema(['type' => 'number', 'format' => 'float']), true),
                new Field('', new IsFloat()),
            ],
            'detailed input' => [
                new Specification\Numeric(
                    '',
                    new Schema(
                        [
                            'type' => 'integer',
                            'exclusiveMinimum' => true,
                            'exclusiveMaximum' => true,
                            'maximum' => 4,
                            'minimum' => 0,
                            'multipleOf' => 3,
                            'enum' => [1, 2, 3, null],
                            'format' => 'nullable int',
                            'nullable' => true,
                        ]
                    ),
                    false
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Field(
                        '',
                        new ToInt(),
                        new IsInt(),
                        new Contained([1, 2, 3, null]),
                        new Maximum(4, true),
                        new Minimum(0, true),
                        new MultipleOf(3)
                    )
                ),
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
