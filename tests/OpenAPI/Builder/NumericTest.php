<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder;

use Membrane\Filter\Type\ToFloat;
use Membrane\Filter\Type\ToInt;
use Membrane\Filter\Type\ToNumber;
use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\Numeric;
use Membrane\OpenAPI\Specification;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30, V31};
use Membrane\OpenAPIReader\ValueObject\Value;
use Membrane\Processor;
use Membrane\Processor\AnyOf;
use Membrane\Processor\Field;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\Numeric\Maximum;
use Membrane\Validator\Numeric\Minimum;
use Membrane\Validator\Numeric\MultipleOf;
use Membrane\Validator\String\IntString;
use Membrane\Validator\String\NumericString;
use Membrane\Validator\Type\IsFloat;
use Membrane\Validator\Type\IsInt;
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
    public static function specificationsToBuild(): array
    {
        return [
            'integer input to convert from string' => [
                new Field('int-from-string', new IntString(), new ToInt()),
                'int-from-string',
                new V30\Schema(new Identifier(''), new Partial\Schema(type: 'integer'))->value,
                true,
            ],
            'strict integer input' => [
                new Field('strict-integer', new IsInt()),
                'strict-integer',
                new V30\Schema(new Identifier(''), new Partial\Schema(type: 'integer'))->value,
            ],
            'number input to convert from string' => [
                new Field('number-from-string', new NumericString(), new ToNumber()),
                'number-from-string',
                new V30\Schema(new Identifier(''), new Partial\Schema(type: 'number'))->value,
                true,
            ],
            'strict number input' => [
                new Field('strict-number', new IsNumber()),
                'strict-number',
                new V30\Schema(new Identifier(''), new Partial\Schema(type: 'number'))->value,
            ],
            'float input to convert from string' => [
                new Field('float-from-string', new NumericString(), new ToFloat()),
                'float-from-string',
                new V30\Schema(new Identifier(''), new Partial\Schema(type: 'number', format: 'float'))->value,
                true,
            ],
            'strict float input' => [
                new Field('strict-float', new IsFloat()),
                'strict-float',
                new V30\Schema(new Identifier(''), new Partial\Schema(type: 'number', format: 'float'))->value,
            ],
            'detailed input to convert from string' => [
                new Field(
                    'maximum-detail-from-string',
                    new IntString(),
                    new ToInt(),
                    new Contained([1, 2, 3, null]),
                    new Maximum(4, true),
                    new Minimum(0, true),
                    new MultipleOf(3)
                ),
                'maximum-detail-from-string',
                new V30\Schema(new Identifier(''), new Partial\Schema(
                    type: 'integer',
                    enum: [new Value(1), new Value(2), new Value(3), new Value(null)],
                    multipleOf: 3,
                    exclusiveMaximum: true,
                    exclusiveMinimum: true,
                    maximum: 4,
                    minimum: 0,
                    format: 'int',
                ))->value,
                true,
            ],
            'strict detailed input' => [
                new Field(
                    'strict-maximum-detail',
                    new IsInt(),
                    new Contained([1, 2, 3, null]),
                    new Maximum(4, true),
                    new Minimum(0, true),
                    new MultipleOf(3)
                ),
                'strict-maximum-detail',
                new V30\Schema(new Identifier(''), new Partial\Schema(
                    type: 'integer',
                    enum: [new Value(1), new Value(2), new Value(3), new Value(null)],
                    multipleOf: 3,
                    exclusiveMaximum: true,
                    exclusiveMinimum: true,
                    maximum: 4,
                    minimum: 0,
                    format: 'int',
                ))->value,
            ],
        ];
    }

    #[DataProvider('specificationsToBuild')]
    #[Test]
    public function buildTest(
        Processor $expected,
        string $fieldName,
        V30\Keywords | V31\Keywords $keywords,
        bool $convertFromString = false,
        bool $convertFromArray = false,
        ?string $style = null,
    ): void {
        self::assertEquals(
            $expected,
            new Numeric()->build(
                $fieldName,
                $keywords,
                $convertFromString,
                $convertFromArray,
                $style,
            ),
        );
    }
}
