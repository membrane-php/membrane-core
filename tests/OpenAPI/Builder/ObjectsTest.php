<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder;

use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\Numeric;
use Membrane\OpenAPI\Builder\Objects;
use Membrane\OpenAPI\Builder\Strings;
use Membrane\OpenAPI\Builder\TrueFalse;
use Membrane\OpenAPI\Specification;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30, V31};
use Membrane\OpenAPIReader\ValueObject\Value;
use Membrane\Processor;
use Membrane\Processor\AnyOf;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\DefaultProcessor;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Processor\OneOf;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\Collection\Count;
use Membrane\Validator\FieldSet\FixedFields;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Type\IsArray;
use Membrane\Validator\Type\IsBool;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Objects::class)]
#[CoversClass(APIBuilder::class)]
#[UsesClass(Numeric::class)]
#[UsesClass(TrueFalse::class)]
#[UsesClass(Strings::class)]
#[UsesClass(AnyOf::class)]
#[UsesClass(Specification\APISchema::class)]
#[UsesClass(BeforeSet::class)]
#[UsesClass(DefaultProcessor::class)]
#[UsesClass(Field::class)]
#[UsesClass(FieldSet::class)]
#[UsesClass(OneOf::class)]
#[UsesClass(Contained::class)]
#[UsesClass(FixedFields::class)]
#[UsesClass(RequiredFields::class)]
#[UsesClass(Count::class)]
class ObjectsTest extends TestCase
{
    public static function specificationsToBuild(): array
    {
        /**
         * @param array<Value> $enum
         * @param array<string> $required
         * @param array<string, Partial\Schema> $properties
         */
        $keywordsV30 = static fn (
            ?array $enum = null,
            ?int $maxProperties = null,
            int $minProperties = 0,
            array $required = [],
            array $properties = [],
            bool|Partial\Schema $additionalProperties = true,
        ) => new V30\Schema(new Identifier(''), new Partial\Schema(
            type: 'object',
            enum: $enum,
            maxProperties: $maxProperties,
            minProperties: $minProperties,
            required: $required,
            properties: $properties,
            additionalProperties: $additionalProperties,
        ))->value;

        /** @param array<string, Partial\Schema> $properties */
        $keywordsV31 = static fn (
            ?int $maxProperties = null,
            int $minProperties = 0,
            array $properties = [],
            bool|Partial\Schema $additionalProperties = true,
        ) => new V31\Schema(new Identifier(''), new Partial\Schema(
            type: 'object',
            maxProperties: $maxProperties,
            minProperties: $minProperties,
            properties: $properties,
            additionalProperties: $additionalProperties,
        ))->value;

        return [
            '3.0 mvp' => [
                new FieldSet('3.0-mvp', new BeforeSet(new IsArray())),
                '3.0-mvp',
                $keywordsV30(),
            ],
            '3.0 minProperties' => [
                new FieldSet(
                    '3.0-min',
                    new BeforeSet(new IsArray(), new Count(1)),
                ),
                '3.0-min',
                $keywordsV30(minProperties: 1),
            ],
            '3.0 maxProperties' => [
                new FieldSet(
                    '3.0-max',
                    new BeforeSet(new IsArray(), new Count(0, 1)),
                ),
                '3.0-max',
                $keywordsV30(maxProperties: 1),
            ],
            '3.0 minProperties and maxProperties' => [
                new FieldSet(
                    '3.0-min-max',
                    new BeforeSet(new IsArray(), new Count(1, 1)),
                ),
                '3.0-min-max',
                $keywordsV30(maxProperties: 1, minProperties: 1),
            ],
            '3.0 integer property' => [
                new FieldSet(
                    '3.0-prop-int',
                    new BeforeSet(new IsArray()),
                    new Field('a', new IsInt()),
                ),
                '3.0-prop-int',
                $keywordsV30(
                    properties: ['a' => new Partial\Schema(type: 'integer')],
                ),
            ],
            '3.0 additionalProperties:false' => [
                new FieldSet(
                    '3.0-additional',
                    new BeforeSet(new IsArray(), new FixedFields('a')),
                    new Field('a', new IsInt()),
                ),
                '3.0-additional',
                $keywordsV30(
                    properties: ['a' => new Partial\Schema(type: 'integer')],
                    additionalProperties: false,
                ),
            ],
            '3.0 property of string|integer' => [
                new FieldSet(
                    '3.0-prop-stringint',
                    new BeforeSet(new IsArray()),
                    new AnyOf(
                        'a',
                        new Field('Branch-1', new IsString()),
                        new Field('Branch-2', new IsInt()),
                    ),
                ),
                '3.0-prop-stringint',
                $keywordsV30(
                    properties: ['a' => new Partial\Schema(anyOf: [
                        new Partial\Schema(type: 'string'),
                        new Partial\Schema(type: 'integer'),
                    ])],
                ),
            ],
            '3.1 property of string|integer' => [
                new FieldSet(
                    '3.1-prop-int',
                    new BeforeSet(new IsArray()),
                    new AnyOf(
                        'a',
                        new Field('a', new IsString()),
                        new Field('a', new IsInt()),
                    ),
                ),
                '3.1-prop-int',
                $keywordsV31(
                    properties: ['a' => new Partial\Schema(
                        type: ['string', 'integer'],
                    )],
                ),
            ],
            'complex additional properties' => [
                new FieldSet(
                    '3.0-additional-schema',
                    new BeforeSet(new IsArray(), new Count(2, 5)),
                    new DefaultProcessor(
                        new OneOf(
                            '',
                            new Field('Branch-1', new IsBool()),
                            new Field('Branch-2', new IsInt()),
                        )
                    )
                ),
                '3.0-additional-schema',
                $keywordsV30(
                    maxProperties: 5,
                    minProperties: 2,
                    additionalProperties: new Partial\Schema(oneOf: [
                        new Partial\Schema(type: 'boolean'),
                        new Partial\Schema(type: 'integer'),
                    ]),
                ),
            ],
            'detailed input' => [
                new FieldSet(
                    'max-v-p',
                    new BeforeSet(
                        new IsArray(),
                        new Contained([['id' => 5, 'name' => 'Blink'], null]),
                        new RequiredFields('id', 'name')
                    ),
                    DefaultProcessor::fromFiltersAndValidators(new IsString()),
                    new Field('id', new IsInt()),
                    new Field('name', new IsString())
                ),
                'max-v-p',
                $keywordsV30(
                    enum: [new Value(['id' => 5, 'name' => 'Blink']), new Value(null)],
                    required: ['id', 'name'],
                    properties: [
                        'id' => new Partial\Schema(type: 'integer'),
                        'name' => new Partial\Schema(type: 'string'),
                    ],
                    additionalProperties: new Partial\Schema(type: 'string'),
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
        bool $explode = false,
    ): void {
        self::assertEquals(
            $expected,
            new Objects()->build(
                $fieldName,
                $keywords,
                $convertFromString,
                $convertFromArray,
                $style,
                $explode,
            ),
        );
    }
}
