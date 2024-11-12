<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\Arrays;
use Membrane\OpenAPI\Builder\Numeric;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Specification;
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
                new Specification\Arrays('', new Schema(['type' => 'array'])),
                new Collection('', new BeforeSet(new IsList())),
            ],
            'detailed input' => [
                new Specification\Arrays(
                    '',
                    new Schema(
                        [
                            'type' => 'array',
                            'items' => new Schema(['type' => 'integer']),
                            'maxItems' => 3,
                            'minItems' => 1,
                            'uniqueItems' => true,
                            'enum' => [[1, 2, 3], null],
                            'format' => 'array of ints',
                            'nullable' => false,
                        ]
                    )
                ),
                new Collection(
                    '',
                    new BeforeSet(new IsList(), new Contained([[1, 2, 3], null]), new Count(1, 3), new Unique()),
                    new Field('', new IsInt())
                ),
            ],
            'detailed nullable input' => [
                new Specification\Arrays(
                    '',
                    new Schema(
                        [
                            'type' => 'array',
                            'items' => new Schema(['type' => 'integer']),
                            'maxItems' => 3,
                            'minItems' => 1,
                            'uniqueItems' => true,
                            'enum' => [[1, 2, 3], null],
                            'format' => 'array of ints',
                            'nullable' => true,
                        ]
                    )
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

    #[DataProvider('specificationsToBuild')]
    #[Test]
    public function buildTest(Specification\Arrays $specification, Processor $expected): void
    {
        $sut = new Arrays();

        $actual = $sut->build($specification);

        self::assertEquals($expected, $actual);
    }
}
