<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Builder;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Builder\APIBuilder;
use Membrane\OpenAPI\Builder\Numeric;
use Membrane\OpenAPI\Builder\Objects;
use Membrane\OpenAPI\Builder\Strings;
use Membrane\OpenAPI\Builder\TrueFalse;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Processor\OneOf;
use Membrane\OpenAPI\Specification;
use Membrane\OpenAPIReader\OpenAPIVersion;
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
use Membrane\Validator\Type\IsNull;
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
            'minimum input' => [
                new Specification\Objects(OpenAPIVersion::Version_3_0, '', new Schema(['type' => 'object'])),
                new FieldSet('', new BeforeSet(new IsArray())),
            ],
            'minProperties greater than zero' => [
                new Specification\Objects(OpenAPIVersion::Version_3_0, '', new Schema(['type' => 'object', 'minProperties' => 1])),
                new FieldSet('', new BeforeSet(new IsArray(), new Count(1))),
            ],
            'maxProperties is set' => [
                new Specification\Objects(OpenAPIVersion::Version_3_0, '', new Schema(['type' => 'object', 'maxProperties' => 1])),
                new FieldSet('', new BeforeSet(new IsArray(), new Count(0, 1))),
            ],
            'minProperties and maxProperties is set' => [
                new Specification\Objects(
                    OpenAPIVersion::Version_3_0,
                    '',
                    new Schema(['type' => 'object', 'minProperties' => 1, 'maxProperties' => 1])
                ),
                new FieldSet('', new BeforeSet(new IsArray(), new Count(1, 1))),
            ],
            'additionalProperties set to false' => [
                new Specification\Objects(
                    OpenAPIVersion::Version_3_0,
                    '',
                    new Schema([
                        'type' => 'object',
                        'properties' => [
                            'a' => new Schema(['type' => 'integer']),
                        ],
                        'additionalProperties' => false,
                    ])
                ),
                new FieldSet('', new BeforeSet(new IsArray(), new FixedFields('a')), new Field('a', new IsInt())),
            ],
            'complex additional properties' => [
                new Specification\Objects(
                    OpenAPIVersion::Version_3_0,
                    '',
                    new Schema([
                        'type' => 'object',
                        'minProperties' => 2,
                        'maxProperties' => 5,
                        'additionalProperties' => [
                            'oneOf' => [
                                new Schema(['type' => 'boolean']),
                                new Schema(['type' => 'integer']),
                            ],
                        ],
                    ])
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
                    OpenAPIVersion::Version_3_0,
                    '',
                    new Schema(
                        [
                            'type' => 'object',
                            'properties' => [
                                'id' => new Schema(['type' => 'integer']),
                                'name' => new Schema(['type' => 'string']),
                            ],
                            'required' => ['id', 'name'],
                            'format' => 'pet',
                            'enum' => [['id' => 5, 'name' => 'Blink'], null],
                            'nullable' => true,
                            'additionalProperties' => new Schema(['type' => 'string']),
                        ]
                    )
                ),
                new AnyOf(
                    '',
                    new Field('', new IsNull()),
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
                ),
            ],
        ];
    }

    #[DataProvider('specificationsToBuild')]
    #[Test]
    public function buildTest(Specification\Objects $specification, Processor $expected): void
    {
        $sut = new Objects();

        $actual = $sut->build($specification);

        self::assertEquals($expected, $actual);
    }
}
