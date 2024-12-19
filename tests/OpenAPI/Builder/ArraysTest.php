<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder;

use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\Arrays;
use Membrane\OpenAPI\Builder\Numeric;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Specification;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30, V31};
use Membrane\OpenAPIReader\ValueObject\Value;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Processor\Field;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\Collection\Count;
use Membrane\Validator\Collection\Unique;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsList;
use Membrane\Validator\Type\IsNull;
use Membrane\Validator\Type\IsString;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Arrays::class)]
#[CoversClass(APIBuilder::class)]
#[UsesClass(Numeric::class)]
#[UsesClass(AnyOf::class)]
#[UsesClass(Specification\APISchema::class)]
#[UsesClass(Specification\Numeric::class)]
#[UsesClass(BeforeSet::class)]
#[UsesClass(Collection::class)]
#[UsesClass(Field::class)]
#[UsesClass(Contained::class)]
#[UsesClass(Count::class)]
#[UsesClass(Unique::class)]
class ArraysTest extends TestCase
{
    #[Test]
    public function supportsArraysSpecification(): void
    {
        $specification = self::createStub(Specification\Arrays::class);
        $sut = new Arrays();

        self::assertTrue($sut->supports($specification));
    }

    #[Test]
    public function doesNotSupportNonArraysSpecification(): void
    {
        $specification = self::createStub(\Membrane\Builder\Specification::class);
        $sut = new Arrays();

        self::assertFalse($sut->supports($specification));
    }

    public static function specificationsToBuild(): array
    {
        return [
            '3.0 minimum input' => [
                new Specification\Arrays(
                    OpenAPIVersion::Version_3_0,
                    '',
                    new V30\Schema(new Identifier('test'), new Partial\Schema(
                        type: 'array',
                    )),
                ),
                new Collection('', new BeforeSet(new IsList()), new Field('', new Passes())),
            ],
            '3.1 minimum input' => [
                new Specification\Arrays(
                    OpenAPIVersion::Version_3_1,
                    '',
                    new V31\Schema(new Identifier('test'), new Partial\Schema(
                        type: 'array',
                    )),
                ),
                new Collection('', new BeforeSet(new IsList()), new Field('', new Passes())),
            ],
            '3.0 string|integer items' => [
                new Specification\Arrays(
                    OpenAPIVersion::Version_3_0,
                    '',
                    new V30\Schema(new Identifier('test'), new Partial\Schema(
                        type: 'array',
                        items: new Partial\Schema(
                            anyOf: [
                                new Partial\Schema(type: 'string'),
                                new Partial\Schema(type: 'integer'),
                            ]
                        )
                    )),
                ),
                new Collection(
                    '',
                    new BeforeSet(new IsList()),
                    new AnyOf('', new Field('Branch-1', new IsString()), new Field('Branch-2', new IsInt())),
                ),
            ],
            '3.1 string|integer items' => [
                new Specification\Arrays(
                    OpenAPIVersion::Version_3_1,
                    '',
                    new V31\Schema(new Identifier('test'), new Partial\Schema(
                        type: 'array',
                        items: new Partial\Schema(type: ['string', 'integer']),
                    )),
                ),
                new Collection(
                    '',
                    new BeforeSet(new IsList()),
                    new AnyOf('', new Field('', new IsString()), new Field('', new IsInt())),
                ),
            ],
            '3.1 detailed input' => [
                new Specification\Arrays(
                    OpenAPIVersion::Version_3_1,
                    '',
                    new V31\Schema(new Identifier(''), new Partial\Schema(
                        type: 'array',
                        enum: [new Value([1, 2, 3]), new Value(null)],
                        nullable: false,
                        maxItems: 3,
                        minItems: 1,
                        uniqueItems: true,
                        items: new Partial\Schema(type: 'integer'),
                        format: 'array of ints',
                    )),
                ),
                new Collection(
                    '',
                    new BeforeSet(new IsList(), new Contained([[1, 2, 3], null]), new Count(1, 3), new Unique()),
                    new Field('', new IsInt()),
                ),
            ],
            '3.0 nullable items' => [
                new Specification\Arrays(
                    OpenAPIVersion::Version_3_0,
                    '',
                    new V30\Schema(new Identifier(''), new Partial\Schema(
                        type: 'array',
                        enum: [new Value([1, 2, 3]), new Value(null)],
                        maxItems: 3,
                        minItems: 1,
                        uniqueItems: true,
                        items: new Partial\Schema(type: 'integer', nullable: true),
                        format: 'array of ints',
                    )),
                ),
                new Collection(
                    '',
                    new BeforeSet(new IsList(), new Contained([[1, 2, 3], null]), new Count(1, 3), new Unique()),
                    new AnyOf('', new Field('', new IsInt()), new Field('', new IsNull())),
                ),
            ],
            '3.1 nullable items' => [
                new Specification\Arrays(
                    OpenAPIVersion::Version_3_1,
                    '',
                    new V31\Schema(new Identifier(''), new Partial\Schema(
                        type: 'array',
                        enum: [new Value([1, 2, 3]), new Value(null)],
                        maxItems: 3,
                        minItems: 1,
                        uniqueItems: true,
                        items: new Partial\Schema(type: ['integer', 'null']),
                        format: 'array of ints',
                    )),
                ),
                new Collection(
                    '',
                    new BeforeSet(new IsList(), new Contained([[1, 2, 3], null]), new Count(1, 3), new Unique()),
                    new AnyOf('', new Field('', new IsInt()), new Field('', new IsNull())),
                ),
            ],
        ];
    }

    #[Test]
    #[DataProvider('specificationsToBuild')]
    public function buildTest(Specification\Arrays $specification, Processor $expected): void
    {
        $sut = new Arrays();

        $actual = $sut->build($specification);

        self::assertEquals($expected, $actual);
    }
}
