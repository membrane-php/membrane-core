<?php

declare(strict_types=1);

namespace OpenAPI\Builder;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Builder\Arrays;
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
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Builder\Arrays
 * @covers \Membrane\OpenAPI\Builder\APIBuilder
 * @uses   \Membrane\OpenAPI\Builder\Numeric
 * @uses   \Membrane\OpenAPI\Processor\AnyOf
 * @uses   \Membrane\OpenAPI\Specification\APISchema
 * @uses   \Membrane\OpenAPI\Specification\Numeric
 * @uses   \Membrane\Processor\BeforeSet
 * @uses   \Membrane\Processor\Collection
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Validator\Collection\Contained
 * @uses   \Membrane\Validator\Collection\Count
 * @uses   \Membrane\Validator\Collection\Unique
 */
class ArraysTest extends TestCase
{
    public function specificationsToSupport(): array
    {
        return [
            [
                new class() implements \Membrane\Builder\Specification {
                },
                false,
            ],
            [self::createStub(Specification\Arrays::class), true],
        ];
    }

    /**
     * @test
     * @dataProvider specificationsToSupport
     */
    public function supportsTest(\Membrane\Builder\Specification $specification, bool $expected): void
    {
        $sut = new Arrays();

        self::assertSame($expected, $sut->supports($specification));
    }

    public function specificationsToBuild(): array
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

    /**
     * @test
     * @dataProvider specificationsToBuild
     */
    public function buildTest(Specification\Arrays $specification, Processor $expected): void
    {
        $sut = new Arrays();

        $actual = $sut->build($specification);

        self::assertEquals($expected, $actual);
    }
}
