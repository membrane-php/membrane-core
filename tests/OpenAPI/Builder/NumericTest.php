<?php

declare(strict_types=1);

namespace OpenAPI\Builder;

use cebe\openapi\spec\Schema;
use Membrane\Filter\Type\ToFloat;
use Membrane\Filter\Type\ToInt;
use Membrane\Filter\Type\ToNumber;
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
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Builder\Numeric
 * @covers \Membrane\OpenAPI\Builder\APIBuilder
 * @uses   \Membrane\OpenAPI\Processor\AnyOf
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Validator\Collection\Contained
 * @uses   \Membrane\Validator\Numeric\Maximum
 * @uses   \Membrane\Validator\Numeric\Minimum
 * @uses   \Membrane\Validator\Numeric\MultipleOf
 */
class NumericTest extends TestCase
{
    public function specificationsToSupport(): array
    {
        return [
            [
                new class() implements \Membrane\Builder\Specification {
                },
                false,
            ],
            [self::createStub(Specification\Numeric::class), true],
        ];
    }

    /**
     * @test
     * @dataProvider specificationsToSupport
     */
    public function supportsTest(\Membrane\Builder\Specification $specification, bool $expected): void
    {
        $sut = new Numeric();

        self::assertSame($expected, $sut->supports($specification));
    }

    public function specificationsToBuild(): array
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

    /**
     * @test
     * @dataProvider specificationsToBuild
     */
    public function buildTest(Specification\Numeric $specification, Processor $expected): void
    {
        $sut = new Numeric();

        $actual = $sut->build($specification);

        self::assertEquals($expected, $actual);
    }
}
