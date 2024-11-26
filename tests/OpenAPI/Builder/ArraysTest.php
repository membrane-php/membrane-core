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
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30};
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
            'minimum input' => [
                new Specification\Arrays(
                    OpenAPIVersion::Version_3_0,
                    '',
                    new V30\Schema(new Identifier('test'), new Partial\Schema(
                        type: 'array',
                    )),
                ),
                new Collection('', new BeforeSet(new IsList())),
            ],
            'detailed input' => [
                new Specification\Arrays(
                    OpenAPIVersion::Version_3_0,
                    '',
                    new V30\Schema(new Identifier(''), new Partial\Schema(
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
                    new Field('', new IsInt())
                ),
            ],
            'detailed nullable input' => [
                new Specification\Arrays(
                    OpenAPIVersion::Version_3_0,
                    '',
                    new V30\Schema(new Identifier(''), new Partial\Schema(
                        type: 'array',
                        enum: [new Value([1, 2, 3]), new Value(null)],
                        nullable: true,
                        maxItems: 3,
                        minItems: 1,
                        uniqueItems: true,
                        items: new Partial\Schema(type: 'integer'),
                        format: 'array of ints',
                    )),
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
                    new Collection(
                        '',
                        new BeforeSet(new IsList(), new Contained([[1, 2, 3], null]), new Count(1, 3), new Unique()),
                        new Field('', new IsInt())
                    )
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
