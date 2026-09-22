<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder;

use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\Arrays;
use Membrane\OpenAPI\Builder\Numeric;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30, V31};
use Membrane\OpenAPIReader\ValueObject\Value;
use Membrane\Processor;
use Membrane\Processor\AnyOf;
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
#[UsesClass(BeforeSet::class)]
#[UsesClass(Collection::class)]
#[UsesClass(Field::class)]
#[UsesClass(Contained::class)]
#[UsesClass(Count::class)]
#[UsesClass(Unique::class)]
class ArraysTest extends TestCase
{
    public static function specificationsToBuild(): array
    {
        return [
            '3.0 minimum input' => [
                new Collection(
                    '3.0-mvp',
                    new BeforeSet(new IsList()),
                    new Field('', new Passes()),
                ),
                '3.0-mvp',
                self::keywordsV30(),
            ],
            '3.1-mvp' => [
                new Collection(
                    '3.1-mvp',
                    new BeforeSet(new IsList()),
                    new Field('', new Passes()),
                ),
                '3.1-mvp',
                self::keywordsV31(),
            ],
            '3.0 array<string|integer>' => [
                new Collection(
                    '3.0-items-stringint',
                    new BeforeSet(new IsList()),
                    new AnyOf(
                        '',
                        new Field('Branch-1', new IsString()),
                        new Field('Branch-2', new IsInt()),
                    ),
                ),
                '3.0-items-stringint',
                self::keywordsV30(
                    items: new Partial\Schema(
                        anyOf: [
                            new Partial\Schema(type: 'string'),
                            new Partial\Schema(type: 'integer'),
                        ]
                    )
                ),
            ],
            '3.1 array<string|integer>' => [
                new Collection(
                    '3.1-items-stringint',
                    new BeforeSet(new IsList()),
                    new AnyOf(
                        '',
                        new Field('', new IsString()),
                        new Field('', new IsInt()),
                    ),
                ),
                '3.1-items-stringint',
                self::keywordsV31(
                    items: new Partial\Schema(
                        type: ['string', 'integer'],
                    ),
                ),
            ],
            '3.1 detailed input' => [
                new Collection(
                    '3.1-maxvp',
                    new BeforeSet(new IsList(), new Contained([[1, 2, 3], null]), new Count(1, 3), new Unique()),
                    new Field('', new IsInt()),
                ),
                '3.1-maxvp',
                self::keywordsV31(
                    enum: [new Value([1, 2, 3]), new Value(null)],
                    maxItems: 3,
                    minItems: 1,
                    uniqueItems: true,
                    items: new Partial\Schema(type: 'integer'),
                ),
            ],
            '3.0 nullable items' => [
                new Collection(
                    '3.0-items-nullable',
                    new BeforeSet(new IsList(), new Contained([[1, 2, 3], null]), new Count(1, 3), new Unique()),
                    new AnyOf('', new Field('', new IsInt()), new Field('', new IsNull())),
                ),
                '3.0-items-nullable',
                self::keywordsV30(
                    enum: [new Value([1, 2, 3]), new Value(null)],
                    maxItems: 3,
                    minItems: 1,
                    uniqueItems: true,
                    items: new Partial\Schema(type: 'integer', nullable: true),
                ),
            ],
            '3.1 nullable items' => [
                new Collection(
                    '3.1-items-nullable',
                    new BeforeSet(new IsList(), new Contained([[1, 2, 3], null]), new Count(1, 3), new Unique()),
                    new AnyOf('', new Field('', new IsInt()), new Field('', new IsNull())),
                ),
                '3.1-items-nullable',
                self::keywordsV31(
                    enum: [new Value([1, 2, 3]), new Value(null)],
                    maxItems: 3,
                    minItems: 1,
                    uniqueItems: true,
                    items: new Partial\Schema(type: ['integer', 'null']),
                ),
            ],
        ];
    }

    #[Test]
    #[DataProvider('specificationsToBuild')]
    public function buildTest(
        Processor $expected,
        string $fieldName,
        V30\Keywords | V31\Keywords $keywords,
        bool $convertFromString = false,
        bool $convertFromArray = false,
        ?string $style = null,
        ?bool $explode = null,
    ): void {
        self::assertEquals($expected, new Arrays()->build(
            $fieldName,
            $keywords,
            $convertFromString,
            $convertFromArray,
            $style,
            $explode,
        ));
    }

    private static function keywordsV30(
        ?array $enum = null,
        ?int $maxItems = null,
        int $minItems = 0,
        bool $uniqueItems = false,
        ?Partial\Schema $items = null,
    ): V30\Keywords {
        return new V30\Schema(new Identifier(''), new Partial\Schema(
            type: 'array',
            enum: $enum,
            maxItems: $maxItems,
            minItems: $minItems,
            uniqueItems: $uniqueItems,
            items: $items,
        ))->value;
    }

    private static function keywordsV31(
        ?array $enum = null,
        ?int $maxItems = null,
        int $minItems = 0,
        bool $uniqueItems = false,
        ?Partial\Schema $items = null,
    ): V31\Keywords {
        return new V31\Schema(new Identifier(''), new Partial\Schema(
            type: 'array',
            enum: $enum,
            maxItems: $maxItems,
            minItems: $minItems,
            uniqueItems: $uniqueItems,
            items: $items,
        ))->value;
    }
}
