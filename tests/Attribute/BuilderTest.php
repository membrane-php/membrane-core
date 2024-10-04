<?php

declare(strict_types=1);

namespace Membrane\Tests\Attribute;

use Membrane\Attribute\Builder;
use Membrane\Attribute\ClassWithAttributes;
use Membrane\Attribute\FilterOrValidator;
use Membrane\Attribute\OverrideProcessorType;
use Membrane\Attribute\SetFilterOrValidator;
use Membrane\Attribute\Subtype;
use Membrane\Builder\Specification;
use Membrane\Exception\CannotProcessProperty;
use Membrane\Filter\CreateObject\WithNamedArguments;
use Membrane\Filter\Type\ToBackedEnum;
use Membrane\Filter\Type\ToString;
use Membrane\Processor\AfterSet;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Tests\Fixtures\Attribute\ArraySumFilter;
use Membrane\Tests\Fixtures\Attribute\ClassThatOverridesProcessorType;
use Membrane\Tests\Fixtures\Attribute\ClassWithClassArrayPropertyIsIntValidator;
use Membrane\Tests\Fixtures\Attribute\ClassWithClassProperty;
use Membrane\Tests\Fixtures\Attribute\ClassWithCompoundPropertyType;
use Membrane\Tests\Fixtures\Attribute\ClassWithDateTimeProperty;
use Membrane\Tests\Fixtures\Attribute\ClassWithIntArrayPropertyBeforeSet;
use Membrane\Tests\Fixtures\Attribute\ClassWithIntArrayPropertyIsIntValidator;
use Membrane\Tests\Fixtures\Attribute\ClassWithIntProperty;
use Membrane\Tests\Fixtures\Attribute\ClassWithIntPropertyIgnoredProperty;
use Membrane\Tests\Fixtures\Attribute\ClassWithIntPropertyIsIntValidator;
use Membrane\Tests\Fixtures\Attribute\ClassWithNestedCollection;
use Membrane\Tests\Fixtures\Attribute\ClassWithNoSubTypeHint;
use Membrane\Tests\Fixtures\Attribute\ClassWithNoTypeHint;
use Membrane\Tests\Fixtures\Attribute\ClassWithPromotedPropertyAfterSet;
use Membrane\Tests\Fixtures\Attribute\ClassWithStringPropertyBeforeSet;
use Membrane\Tests\Fixtures\Attribute\Docs\BlogPost;
use Membrane\Tests\Fixtures\Attribute\Docs\BlogPostFromNamedArguments;
use Membrane\Tests\Fixtures\Attribute\Docs\BlogPostIsItAString;
use Membrane\Tests\Fixtures\Attribute\Docs\BlogPostMakeItAString;
use Membrane\Tests\Fixtures\Attribute\Docs\BlogPostMaxTags;
use Membrane\Tests\Fixtures\Attribute\Docs\BlogPostRegexAndMaxLength;
use Membrane\Tests\Fixtures\Attribute\Docs\BlogPostRequiredFields;
use Membrane\Tests\Fixtures\Attribute\Docs\BlogPostWithAllOf;
use Membrane\Tests\Fixtures\Attribute\EmptyClass;
use Membrane\Tests\Fixtures\Attribute\EmptyClassWithIgnoredProperty;
use Membrane\Tests\Fixtures\Attribute\EnumProperties;
use Membrane\Tests\Fixtures\Attribute\EnumPropertiesWithAttributes;
use Membrane\Tests\Fixtures\Enum\IntBackedDummy;
use Membrane\Tests\Fixtures\Enum\StringBackedDummy;
use Membrane\Validator\Collection\Count;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\String\Length;
use Membrane\Validator\String\Regex;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsList;
use Membrane\Validator\Type\IsString;
use Membrane\Validator\Utility\AllOf;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Builder::class)]
#[CoversClass(CannotProcessProperty::class)]
#[UsesClass(ClassWithAttributes::class)]
#[UsesClass(FilterOrValidator::class)]
#[UsesClass(SetFilterOrValidator::class)]
#[UsesClass(OverrideProcessorType::class)]
#[UsesClass(Subtype::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
#[UsesClass(FieldName::class)]
#[UsesClass(Collection::class)]
#[UsesClass(FieldSet::class)]
#[UsesClass(Field::class)]
#[UsesClass(BeforeSet::class)]
#[UsesClass(AfterSet::class)]
#[UsesClass(RequiredFields::class)]
#[UsesClass(IsList::class)]
#[UsesClass(IsInt::class)]
#[UsesClass(IsString::class)]
#[UsesClass(ToString::class)]
#[UsesClass(Length::class)]
#[UsesClass(Regex::class)]
#[UsesClass(AllOf::class)]
#[UsesClass(Count::class)]
#[UsesClass(WithNamedArguments::class)]
class BuilderTest extends TestCase
{
    #[Test]
    public function supportsReturnsFalseIfSpecificationIsNotClassWithAttributes(): void
    {
        $specification = new class implements Specification {
        };
        $builder = new Builder();

        $result = $builder->supports($specification);

        self::assertFalse($result);
    }

    #[Test]
    public function supportsReturnsTrueIfSpecificationIsClassWithAttributes(): void
    {
        $class = new class {
        };
        $specification = new ClassWithAttributes(get_class($class));
        $builder = new Builder();

        $result = $builder->supports($specification);

        self::assertTrue($result);
    }

    #[Test]
    public function noTypeHintThrowsException(): void
    {
        $specification = new ClassWithAttributes(ClassWithNoTypeHint::class);
        $builder = new Builder();

        self::expectException(CannotProcessProperty::class);
        self::expectExceptionMessage('Property undefinedProperty does not define it\'s type');

        $builder->build($specification);
    }

    #[Test]
    public function noSubTypeHintThrowsException(): void
    {
        $specification = new ClassWithAttributes(ClassWithNoSubTypeHint::class);
        $builder = new Builder();

        self::expectException(CannotProcessProperty::class);
        self::expectExceptionMessage('Property arrayOfMystery is a collection but does not define it\'s subtype');

        $builder->build($specification);
    }

    #[Test]
    public function compoundPropertyThrowsException(): void
    {
        $specification = new ClassWithAttributes(ClassWithCompoundPropertyType::class);
        $builder = new Builder();

        self::expectException(CannotProcessProperty::class);
        self::expectExceptionMessage(
            'Property compoundProperty uses a compound type hint, these are not currently supported'
        );

        $builder->build($specification);
    }

    #[Test]
    public function nestedCollectionThrowsException(): void
    {
        $specification = new ClassWithAttributes(ClassWithNestedCollection::class);
        $builder = new Builder();

        self::expectException(CannotProcessProperty::class);
        self::expectExceptionMessage(
            'Property arrayOfArrays is a collection and defines it\'s subtype as array. ' .
            'Nested collections are not currently supported'
        );

        $builder->build($specification);
    }

    public static function dataSetOfClassesToBuild(): array
    {
        return [
            EmptyClass::class => [
                new ClassWithAttributes(EmptyClass::class),
                new FieldSet(''),
            ],
            EmptyClassWithIgnoredProperty::class => [
                new ClassWithAttributes(EmptyClassWithIgnoredProperty::class),
                new FieldSet(''),
            ],
            ClassWithDateTimeProperty::class => [
                new ClassWithAttributes(ClassWithDateTimeProperty::class),
                new FieldSet('', new Field('dateTime')),
            ],
            ClassWithIntProperty::class => [
                new ClassWithAttributes(ClassWithIntProperty::class),
                new FieldSet('', new Field('integerProperty')),
            ],
            ClassWithIntPropertyIgnoredProperty::class => [
                new ClassWithAttributes(ClassWithIntPropertyIgnoredProperty::class),
                new FieldSet('', new Field('integerProperty')),
            ],
            ClassWithIntPropertyIsIntValidator::class => [
                new ClassWithAttributes(ClassWithIntPropertyIsIntValidator::class),
                new FieldSet('', new Field('integerProperty', new IsInt())),
            ],
            EnumPropeties::class => [
                new ClassWithAttributes(EnumProperties::class),
                new FieldSet(
                    '',
                    new Field('stringBackedEnum'),
                    new Field('intBackedEnum'),
                )
            ],
            EnumPropertiesWithAttributes::class => [
                new ClassWithAttributes(EnumPropertiesWithAttributes::class),
                new FieldSet(
                    '',
                    new Field('stringBackedEnum', new ToBackedEnum(StringBackedDummy::class)),
                    new Field('intBackedEnum', new ToBackedEnum(IntBackedDummy::class)),
                )
            ],
            ClassWithIntArrayPropertyIsIntValidator::class => [
                new ClassWithAttributes(ClassWithIntArrayPropertyIsIntValidator::class),
                new FieldSet(
                    '',
                    new Collection(
                        'arrayOfInts',
                        new Field('arrayOfInts', new IsInt())
                    )
                ),
            ],
            ClassWithClassArrayPropertyIsIntValidator::class => [
                new ClassWithAttributes(ClassWithClassArrayPropertyIsIntValidator::class),
                new FieldSet(
                    '',
                    new Collection(
                        'arrayOfClasses',
                        new FieldSet('arrayOfClasses', new Field('integerProperty', new IsInt()))
                    )
                ),
            ],
            ClassWithClassProperty::class => [
                new ClassWithAttributes(ClassWithClassProperty::class),
                new FieldSet(
                    '',
                    new FieldSet(
                        'class',
                        new Field('integerProperty', new IsInt())
                    )
                ),
            ],
            ClassWithStringPropertyBeforeSet::class => [
                new ClassWithAttributes(ClassWithStringPropertyBeforeSet::class),
                new FieldSet('', new Field('property'), new BeforeSet(new RequiredFields('property'))),
            ],
            ClassWithIntArrayPropertyBeforeSet::class => [
                new ClassWithAttributes(ClassWithIntArrayPropertyBeforeSet::class),
                new FieldSet(
                    '',
                    new Collection(
                        'arrayOfInts',
                        new BeforeSet(new IsList()),
                        new Field('arrayOfInts', new IsInt())
                    )
                ),
            ],
            ClassWithPromotedPropertyAfterSet::class => [
                new ClassWithAttributes(ClassWithPromotedPropertyAfterSet::class),
                new FieldSet(
                    '',
                    new Field('promotedProperty', new IsInt()),
                    new AfterSet(new WithNamedArguments(ClassWithPromotedPropertyAfterSet::class))
                ),
            ],
            ClassThatOverridesProcessorType::class => [
                new ClassWithAttributes(ClassThatOverridesProcessorType::class),
                new FieldSet(
                    '',
                    new Collection(
                        'sumOfInts',
                        new BeforeSet(new IsList()),
                        new Field('sumOfInts', new IsInt()),
                        new AfterSet(new ArraySumFilter())
                    )
                ),
            ],

        ];
    }

    #[DataProvider('dataSetOfClassesToBuild')]
    #[Test]
    public function BuildingProcessorsTest(Specification $specification, FieldSet $expected): void
    {
        $builder = new Builder();

        $output = $builder->build($specification);

        self::assertEquals($expected, $output);
    }

    public static function dataSetOfInputsAndOutputs(): array
    {
        return [
            EmptyClass::class => [
                new ClassWithAttributes(EmptyClass::class),
                [],
                Result::noResult([]),
            ],
            EmptyClassWithIgnoredProperty::class => [
                new ClassWithAttributes(EmptyClassWithIgnoredProperty::class),
                [],
                Result::noResult([]),
            ],
            ClassWithDateTimeProperty::class => [
                new ClassWithAttributes(ClassWithDateTimeProperty::class),
                [],
                Result::noResult([]),
            ],
            ClassWithIntProperty::class => [
                new ClassWithAttributes(ClassWithIntProperty::class),
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2, 'c' => 3]),
            ],
            ClassWithIntPropertyIgnoredProperty::class => [
                new ClassWithAttributes(ClassWithIntPropertyIgnoredProperty::class),
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2, 'c' => 3]),
            ],
            ClassWithIntPropertyIsIntValidator::class => [
                new ClassWithAttributes(ClassWithIntPropertyIsIntValidator::class),
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2, 'c' => 3]),
            ],
            ClassWithIntArrayPropertyIsIntValidator::class => [
                new ClassWithAttributes(ClassWithIntArrayPropertyIsIntValidator::class),
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2, 'c' => 3]),
            ],
            ClassWithClassArrayPropertyIsIntValidator::class => [
                new ClassWithAttributes(ClassWithClassArrayPropertyIsIntValidator::class),
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2, 'c' => 3]),
            ],
            ClassWithClassProperty::class => [
                new ClassWithAttributes(ClassWithClassProperty::class),
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2, 'c' => 3]),
            ],
            ClassWithStringPropertyBeforeSet::class => [
                new ClassWithAttributes(ClassWithStringPropertyBeforeSet::class),
                ['property' => 1],
                Result::valid(['property' => 1]),
            ],
            ClassWithIntArrayPropertyBeforeSet::class => [
                new ClassWithAttributes(ClassWithIntArrayPropertyBeforeSet::class),
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2, 'c' => 3]),
            ],
            ClassWithPromotedPropertyAfterSet::class => [
                new ClassWithAttributes(ClassWithPromotedPropertyAfterSet::class),
                ['promotedProperty' => 1],
                Result::valid(new ClassWithPromotedPropertyAfterSet(1)),
            ],
            ClassThatOverridesProcessorType::class => [
                new ClassWithAttributes(ClassThatOverridesProcessorType::class),
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2, 'c' => 3]),
            ],

        ];
    }

    #[DataProvider('dataSetOfInputsAndOutputs')]
    #[Test]
    public function InputsAndOutputsTest(Specification $specification, mixed $input, mixed $expected): void
    {
        $builder = new Builder();
        $processor = $builder->build($specification);

        $output = $processor->process(new FieldName(''), $input);

        self::assertEquals($expected, $output);
    }

    public static function dataSetsWithDocExamples(): array
    {
        return [
            'Blog Post: A' => [
                new ClassWithAttributes(BlogPost::class),
                ['title' => 'My Post', 'body' => 'My content'],
                Result::noResult(['title' => 'My Post', 'body' => 'My content']),
            ],
            'Blog Post: B' => [
                new ClassWithAttributes(BlogPost::class),
                [],
                Result::noResult([]),
            ],
            'Blog Post: Required Fields A' => [
                new ClassWithAttributes(BlogPostRequiredFields::class),
                ['title' => false, 'body' => null],
                Result::valid(['title' => false, 'body' => null]),
            ],
            'Blog Post: Required Fields B' => [
                new ClassWithAttributes(BlogPostRequiredFields::class),
                [],
                Result::invalid(
                    [],
                    new MessageSet(
                        new FieldName('', '', ''),
                        new Message('%s is a required field', ['title']),
                        new Message('%s is a required field', ['body'])
                    )
                ),
            ],
            'Blog Post: Is It A String? A' => [
                new ClassWithAttributes(BlogPostIsItAString::class),
                ['title' => 'My Post', 'body' => 'My content'],
                Result::valid(['title' => 'My Post', 'body' => 'My content']),
            ],
            'Blog Post: Is It A String? B' => [
                new ClassWithAttributes(BlogPostIsItAString::class),
                ['title' => false, 'body' => null],
                Result::invalid(
                    ['title' => false, 'body' => null],
                    new MessageSet(
                        new FieldName('title', '', ''),
                        new Message(
                            'IsString validator expects string value, %s passed instead',
                            ['boolean']
                        )
                    ),
                    new MessageSet(
                        new FieldName('body', '', ''),
                        new Message(
                            'IsString validator expects string value, %s passed instead',
                            ['NULL']
                        )
                    )
                ),
            ],
            'Blog Post: Make It A String A' => [
                new ClassWithAttributes(BlogPostMakeItAString::class),
                ['title' => false, 'body' => null],
                Result::valid(['title' => '', 'body' => '']),
            ],
            'Blog Post: Make It A String B' => [
                new ClassWithAttributes(BlogPostMakeItAString::class),
                ['title' => ['a', 'b'], 'body' => 'My content'],
                Result::invalid(
                    ['title' => ['a', 'b'], 'body' => 'My content'],
                    new MessageSet(
                        new FieldName('title', '', ''),
                        new Message(
                            'ToString filter only accepts objects, null or scalar values, %s given',
                            ['array']
                        )
                    )
                ),
            ],
            'Blog Post: Maximum Number Of Tags A' => [
                new ClassWithAttributes(BlogPostMaxTags::class),
                ['title' => '', 'body' => '', 'tags' => ['a', 'b', 'c']],
                Result::valid(['title' => '', 'body' => '', 'tags' => ['a', 'b', 'c']]),
            ],
            'Blog Post: Maximum Number Of Tags B' => [
                new ClassWithAttributes(BlogPostMaxTags::class),
                ['title' => '', 'body' => '', 'tags' => ['a', 'b', 'c', 'd', 'e', 'f']],
                Result::invalid(
                    ['title' => '', 'body' => '', 'tags' => ['a', 'b', 'c', 'd', 'e', 'f']],
                    new MessageSet(
                        new FieldName('', '', '', 'tags'),
                        new Message(
                            'Array is expected have a maximum of %d values',
                            [5]
                        )
                    )
                ),
            ],
            'Blog Post: Regex And Max Length A' => [
                new ClassWithAttributes(BlogPostRegexAndMaxLength::class),
                ['title' => 'My Title', 'body' => '', 'tags' => ['a', 'b', 'c']],
                Result::valid(['title' => 'My Title', 'body' => '', 'tags' => ['a', 'b', 'c']]),
            ],
            'Blog Post: Regex And Max Length B' => [
                new ClassWithAttributes(BlogPostRegexAndMaxLength::class),
                [
                    'title' => 'mY tItLe tHat iS uNnEcEsSaRiLlY lOnG wItH InCoRrEcT cApItIlIzAtIoN',
                    'body' => '',
                    'tags' => ['a', 'b', 'c'],
                ],
                Result::invalid(
                    [
                        'title' => 'mY tItLe tHat iS uNnEcEsSaRiLlY lOnG wItH InCoRrEcT cApItIlIzAtIoN',
                        'body' => '',
                        'tags' => ['a', 'b', 'c'],
                    ],
                    new MessageSet(
                        new FieldName('title', '', ''),
                        new Message(
                            'String is expected to be a maximum of %d characters',
                            [50]
                        )
                    )
                ),
            ],
            'Blog Post: All Of A' => [
                new ClassWithAttributes(BlogPostWithAllOf::class),
                ['title' => 'My Title', 'body' => '', 'tags' => ['a', 'b', 'c']],
                Result::valid(['title' => 'My Title', 'body' => '', 'tags' => ['a', 'b', 'c']]),
            ],
            'Blog Post: All Of B' => [
                new ClassWithAttributes(BlogPostWithAllOf::class),
                [
                    'title' => 'mY tItLe tHat iS uNnEcEsSaRiLlY lOnG wItH InCoRrEcT cApItIlIzAtIoN',
                    'body' => '',
                    'tags' => ['a', 'b', 'c'],
                ],
                Result::invalid(
                    [
                        'title' => 'mY tItLe tHat iS uNnEcEsSaRiLlY lOnG wItH InCoRrEcT cApItIlIzAtIoN',
                        'body' => '',
                        'tags' => ['a', 'b', 'c'],
                    ],
                    new MessageSet(
                        new FieldName('title', '', ''),
                        new Message(
                            'String is expected to be a maximum of %d characters',
                            [50]
                        ),
                        new Message(
                            'String does not match the required pattern %s',
                            ['#^([A-Z][a-z]*\s){0,9}([A-Z][a-z]*)$#']
                        )
                    ),
                ),
            ],
            'Blog Post: Build Your Blog Post From Named Arguments' => [
                new ClassWithAttributes(BlogPostFromNamedArguments::class),
                [
                    'title' => 'My Title',
                    'body' => 'My content',
                    'tags' => ['tag1', 'tag2', 'tag3', 'tag4'],
                ],
                Result::valid(
                    new BlogPostFromNamedArguments(
                        'My Title',
                        'My content',
                        ['tag1', 'tag2', 'tag3', 'tag4'],
                    )
                ),
            ],
        ];
    }

    #[DataProvider('dataSetsWithDocExamples')]
    #[Test]
    public function docExamplesTest(Specification $specification, array $input, Result $expected): void
    {
        $builder = new Builder();
        $processor = $builder->build($specification);

        $result = $processor->process(new FieldName(''), $input);

        self::assertEquals($expected, $result);
    }
}
