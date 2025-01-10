<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder;

use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\Numeric;
use Membrane\OpenAPI\Builder\Objects;
use Membrane\OpenAPI\Builder\Strings;
use Membrane\OpenAPI\Builder\TrueFalse;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Processor\OneOf;
use Membrane\OpenAPI\Specification;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30, V31};
use Membrane\OpenAPIReader\ValueObject\Value;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\DefaultProcessor;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
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
#[UsesClass(Specification\Numeric::class)]
#[UsesClass(Specification\Strings::class)]
#[UsesClass(Specification\TrueFalse::class)]
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
    #[Test]
    public function supportsArraysSpecification(): void
    {
        $specification = self::createStub(Specification\Objects::class);
        $sut = new Objects();

        self::assertTrue($sut->supports($specification));
    }

    #[Test]
    public function doesNotSupportSpecificationsThatAreNotArrays(): void
    {
        $specification = self::createStub(\Membrane\Builder\Specification::class);
        $sut = new Objects();

        self::assertFalse($sut->supports($specification));
    }

    public static function specificationsToBuild(): array
    {
        return [
            '3.0 minimum input' => [
                new Specification\Objects(
                    '',
                    (new V30\Schema(new Identifier(''), new Partial\Schema(type: 'object')))->value,
                ),
                new FieldSet('', new BeforeSet(new IsArray())),
            ],
            '3.0 minProperties greater than zero' => [
                new Specification\Objects(
                    '',
                    (new V30\Schema(new Identifier(''), new Partial\Schema(type: 'object', minProperties: 1)))->value,
                ),
                new FieldSet('', new BeforeSet(new IsArray(), new Count(1))),
            ],
            '3.0 maxProperties' => [
                new Specification\Objects(
                    '',
                    (new V30\Schema(new Identifier(''), new Partial\Schema(type: 'object', maxProperties: 1)))->value,
                ),
                new FieldSet('', new BeforeSet(new IsArray(), new Count(0, 1))),
            ],
            '3.0 minProperties and maxProperties' => [
                new Specification\Objects(
                    '',
                    (new V30\Schema(
                        new Identifier(''), new Partial\Schema(
                        type: 'object',
                        maxProperties: 1,
                        minProperties: 1,
                    )
                    ))->value,
                ),
                new FieldSet('', new BeforeSet(new IsArray(), new Count(1, 1))),
            ],
            '3.0 integer property' => [
                new Specification\Objects(
                    '',
                    (new V30\Schema(
                        new Identifier(''), new Partial\Schema(
                            type: 'object',
                            properties: ['a' => new Partial\Schema(type: 'integer')],
                        )
                    ))->value,
                ),
                new FieldSet('', new BeforeSet(new IsArray()), new Field('a', new IsInt())),
            ],
            '3.0 additionalProperties:false' => [
                new Specification\Objects(
                    '',
                    (new V30\Schema(
                        new Identifier(''), new Partial\Schema(
                        type: 'object',
                        properties: ['a' => new Partial\Schema(type: 'integer')],
                        additionalProperties: false,
                    )
                    ))->value,
                ),
                new FieldSet('', new BeforeSet(new IsArray(), new FixedFields('a')), new Field('a', new IsInt())),
            ],
            '3.0 property of string|integer' => [
                new Specification\Objects(
                    '',
                    (new V30\Schema(
                        new Identifier(''), new Partial\Schema(
                            type: 'object',
                            properties: ['a' => new Partial\Schema(anyOf: [
                                new Partial\Schema(type: 'string'),
                                new Partial\Schema(type: 'integer'),
                            ])],
                        )
                    ))->value,
                ),
                new FieldSet(
                    '',
                    new BeforeSet(new IsArray()),
                    new AnyOf('a', new Field('Branch-1', new IsString()), new Field('Branch-2', new IsInt())),
                ),
            ],
            '3.1 property of string|integer' => [
                new Specification\Objects(
                    '',
                    (new V31\Schema(
                        new Identifier(''), new Partial\Schema(
                            type: 'object',
                            properties: ['a' => new Partial\Schema(type: ['string', 'integer'])],
                        )
                    ))->value,
                ),
                new FieldSet(
                    '',
                    new BeforeSet(new IsArray()),
                    new AnyOf('a', new Field('a', new IsString()), new Field('a', new IsInt())),
                ),
            ],
            'complex additional properties' => [
                new Specification\Objects(
                    '',
                    (new V30\Schema(
                        new Identifier(''), new Partial\Schema(
                        type: 'object',
                        maxProperties: 5,
                        minProperties: 2,
                        additionalProperties: new Partial\Schema(oneOf: [
                            new Partial\Schema(type: 'boolean'),
                            new Partial\Schema(type: 'integer'),
                        ]),
                    )
                    ))->value,
                ),
                new FieldSet(
                    '',
                    new BeforeSet(new IsArray(), new Count(2, 5)),
                    new DefaultProcessor(
                        new OneOf(
                            '',
                            new Field('Branch-1', new IsBool()),
                            new Field('Branch-2', new IsInt()),
                        )
                    )
                ),
            ],
            'detailed input' => [
                new Specification\Objects(
                    '',
                    (new V30\Schema(
                        new Identifier(''), new Partial\Schema(
                        type: 'object',
                        enum: [new Value(['id' => 5, 'name' => 'Blink']), new Value(null)],
                        required: ['id', 'name'],
                        properties: [
                            'id' => new Partial\Schema(type: 'integer'),
                            'name' => new Partial\Schema(type: 'string'),
                        ],
                        additionalProperties: new Partial\Schema(type: 'string'),
                        format: 'pet',
                    )
                    ))->value,
                ),
                new FieldSet(
                    '',
                    new BeforeSet(
                        new IsArray(),
                        new Contained([['id' => 5, 'name' => 'Blink'], null]),
                        new RequiredFields('id', 'name')
                    ),
                    DefaultProcessor::fromFiltersAndValidators(new IsString()),
                    new Field('id', new IsInt()),
                    new Field('name', new IsString())
                )
            ],
        ];
    }

    #[Test]
    #[DataProvider('specificationsToBuild')]
    public function buildTest(Specification\Objects $specification, Processor $expected): void
    {
        $sut = new Objects();

        $actual = $sut->build($specification);

        self::assertEquals($expected, $actual);
    }
}
