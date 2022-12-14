<?php

declare(strict_types=1);

namespace Attribute;

use Membrane\Attribute\Builder;
use Membrane\Attribute\ClassWithAttributes;
use Membrane\Builder\Specification;
use Membrane\Exception\CannotProcessProperty;
use Membrane\Filter\CreateObject\WithNamedArguments;
use Membrane\Fixtures\Attribute\ArraySumFilter;
use Membrane\Fixtures\Attribute\ClassThatOverridesProcessorType;
use Membrane\Fixtures\Attribute\ClassWithClassArrayPropertyIsIntValidator;
use Membrane\Fixtures\Attribute\ClassWithClassProperty;
use Membrane\Fixtures\Attribute\ClassWithCompoundPropertyType;
use Membrane\Fixtures\Attribute\ClassWithDateTimeProperty;
use Membrane\Fixtures\Attribute\ClassWithIntArrayPropertyBeforeSet;
use Membrane\Fixtures\Attribute\ClassWithIntArrayPropertyIsIntValidator;
use Membrane\Fixtures\Attribute\ClassWithIntProperty;
use Membrane\Fixtures\Attribute\ClassWithIntPropertyIgnoredProperty;
use Membrane\Fixtures\Attribute\ClassWithIntPropertyIsIntValidator;
use Membrane\Fixtures\Attribute\ClassWithNestedCollection;
use Membrane\Fixtures\Attribute\ClassWithNoSubTypeHint;
use Membrane\Fixtures\Attribute\ClassWithNoTypeHint;
use Membrane\Fixtures\Attribute\ClassWithPromotedPropertyAfterSet;
use Membrane\Fixtures\Attribute\ClassWithStringPropertyBeforeSet;
use Membrane\Fixtures\Attribute\Docs\BlogPost;
use Membrane\Fixtures\Attribute\Docs\BlogPostFromNamedArguments;
use Membrane\Fixtures\Attribute\Docs\BlogPostIsItAString;
use Membrane\Fixtures\Attribute\Docs\BlogPostMakeItAString;
use Membrane\Fixtures\Attribute\Docs\BlogPostMaxTags;
use Membrane\Fixtures\Attribute\Docs\BlogPostRegexAndMaxLength;
use Membrane\Fixtures\Attribute\Docs\BlogPostRequiredFields;
use Membrane\Fixtures\Attribute\Docs\BlogPostWithAllOf;
use Membrane\Fixtures\Attribute\EmptyClass;
use Membrane\Fixtures\Attribute\EmptyClassWithIgnoredProperty;
use Membrane\Processor\AfterSet;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsList;
use PHPUnit\Framework\TestCase;

/**
 * @covers   \Membrane\Attribute\Builder
 * @covers   \Membrane\Exception\CannotProcessProperty
 * @uses     \Membrane\Attribute\ClassWithAttributes
 * @uses     \Membrane\Attribute\FilterOrValidator
 * @uses     \Membrane\Attribute\SetFilterOrValidator
 * @uses     \Membrane\Attribute\OverrideProcessorType
 * @uses     \Membrane\Attribute\Subtype
 * @uses     \Membrane\Result\Result
 * @uses     \Membrane\Result\MessageSet
 * @uses     \Membrane\Result\Message
 * @uses     \Membrane\Result\FieldName
 * @uses     \Membrane\Processor\Collection
 * @uses     \Membrane\Processor\FieldSet
 * @uses     \Membrane\Processor\Field
 * @uses     \Membrane\Processor\BeforeSet
 * @uses     \Membrane\Processor\AfterSet
 * @uses     \Membrane\Validator\FieldSet\RequiredFields
 * @uses     \Membrane\Validator\Type\IsList
 * @uses     \Membrane\Validator\Type\IsInt
 * @uses     \Membrane\Validator\Type\IsString
 * @uses     \Membrane\Filter\Type\ToString
 * @uses     \Membrane\Validator\String\Length
 * @uses     \Membrane\Validator\String\Regex
 * @uses     \Membrane\Validator\Utility\AllOf
 * @uses     \Membrane\Validator\Collection\Count
 * @uses     \Membrane\Filter\CreateObject\WithNamedArguments
 */
class BuilderTest extends TestCase
{
    /**
     * @test
     */
    public function supportsReturnsFalseIfSpecificationIsNotClassWithAttributes(): void
    {
        $specification = new class implements Specification {
        };
        $builder = new Builder();

        $result = $builder->supports($specification);

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function supportsReturnsTrueIfSpecificationIsClassWithAttributes(): void
    {
        $class = new class {
        };
        $specification = new ClassWithAttributes(get_class($class));
        $builder = new Builder();

        $result = $builder->supports($specification);

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function noTypeHintThrowsException(): void
    {
        $specification = new ClassWithAttributes(ClassWithNoTypeHint::class);
        $builder = new Builder();

        self::expectException(CannotProcessProperty::class);
        self::expectExceptionMessage('Property undefinedProperty does not define it\'s type');

        $builder->build($specification);
    }

    /**
     * @test
     */
    public function noSubTypeHintThrowsException(): void
    {
        $specification = new ClassWithAttributes(ClassWithNoSubTypeHint::class);
        $builder = new Builder();

        self::expectException(CannotProcessProperty::class);
        self::expectExceptionMessage('Property arrayOfMystery is a collection but does not define it\'s subtype');

        $builder->build($specification);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    public function dataSetOfClassesToBuild(): array
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
            ClassWithIntArrayPropertyIsIntValidator::class => [
                new ClassWithAttributes(ClassWithIntArrayPropertyIsIntValidator::class),
                new FieldSet(
                    '', new Collection(
                        'arrayOfInts',
                        new Field('arrayOfInts', new IsInt())
                    )
                ),
            ],
            ClassWithClassArrayPropertyIsIntValidator::class => [
                new ClassWithAttributes(ClassWithClassArrayPropertyIsIntValidator::class),
                new FieldSet(
                    '', new Collection(
                        'arrayOfClasses',
                        new FieldSet('arrayOfClasses', new Field('integerProperty', new IsInt()))
                    )
                ),
            ],
            ClassWithClassProperty::class => [
                new ClassWithAttributes(ClassWithClassProperty::class),
                new FieldSet(
                    '', new FieldSet(
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
                    '', new Collection(
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
                    '', new Collection(
                        'sumOfInts',
                        new BeforeSet(new IsList()),
                        new Field('sumOfInts', new IsInt()),
                        new AfterSet(new ArraySumFilter())
                    )
                ),
            ],

        ];
    }

    /**
     * @test
     * @dataProvider dataSetOfClassesToBuild
     */
    public function BuildingProcessorsTest(Specification $specification, FieldSet $expected): void
    {
        $builder = new Builder();

        $output = $builder->build($specification);

        self::assertEquals($expected, $output);
    }

    public function dataSetOfInputsAndOutputs(): array
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

    /**
     * @test
     * @dataProvider dataSetOfInputsAndOutputs
     */
    public function InputsAndOutputsTest(Specification $specification, mixed $input, mixed $expected): void
    {
        $builder = new Builder();
        $processor = $builder->build($specification);

        $output = $processor->process(new FieldName(''), $input);

        self::assertEquals($expected, $output);
    }

    public function dataSetsWithDocExamples(): array
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
                        new FieldName('title', '', ''), new Message(
                            'IsString validator expects string value, %s passed instead',
                            ['boolean']
                        )
                    ),
                    new MessageSet(
                        new FieldName('body', '', ''), new Message(
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
                        new FieldName('title', '', ''), new Message(
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
                        new FieldName('', '', '', 'tags'), new Message(
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
                        new FieldName('title', '', ''), new Message(
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

    /**
     * @test
     * @dataProvider dataSetsWithDocExamples
     */
    public function docExamplesTest(Specification $specification, array $input, Result $expected): void
    {
        $builder = new Builder();
        $processor = $builder->build($specification);

        $result = $processor->process(new FieldName(''), $input);

        self::assertEquals($expected, $result);
    }

}
