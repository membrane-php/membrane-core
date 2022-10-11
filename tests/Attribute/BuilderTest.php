<?php

declare(strict_types=1);

namespace Attribute;

use Membrane\Attribute\Builder;
use Membrane\Exception\CannotProcessProperty;
use Membrane\Filter\CreateObject\WithNamedArguments;
use Membrane\Fixtures\ArraySumFilter;
use Membrane\Fixtures\ClassThatOverridesProcessorType;
use Membrane\Fixtures\ClassWithClassArrayPropertyIsIntValidator;
use Membrane\Fixtures\ClassWithClassProperty;
use Membrane\Fixtures\ClassWithCompoundPropertyType;
use Membrane\Fixtures\ClassWithDateTimeProperty;
use Membrane\Fixtures\ClassWithIntArrayPropertyBeforeSet;
use Membrane\Fixtures\ClassWithIntArrayPropertyIsIntValidator;
use Membrane\Fixtures\ClassWithIntProperty;
use Membrane\Fixtures\ClassWithIntPropertyIgnoredProperty;
use Membrane\Fixtures\ClassWithIntPropertyIsIntValidator;
use Membrane\Fixtures\ClassWithNestedCollection;
use Membrane\Fixtures\ClassWithNoSubTypeHint;
use Membrane\Fixtures\ClassWithNoTypeHint;
use Membrane\Fixtures\ClassWithPromotedPropertyAfterSet;
use Membrane\Fixtures\ClassWithStringPropertyBeforeSet;
use Membrane\Fixtures\Docs\BlogPostFromNamedArguments;
use Membrane\Fixtures\Docs\BlogPostIsItAString;
use Membrane\Fixtures\Docs\BlogPostMakeItAString;
use Membrane\Fixtures\Docs\BlogPostMaxTags;
use Membrane\Fixtures\Docs\BlogPostRegexAndMaxLength;
use Membrane\Fixtures\Docs\BlogPostRequiredFields;
use Membrane\Fixtures\Docs\BlogPostWithAllOf;
use Membrane\Fixtures\EmptyClass;
use Membrane\Fixtures\EmptyClassWithIgnoredProperty;
use Membrane\Processor\AfterSet;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Object\RequiredFields;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsList;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Attribute\Builder
 * @uses \Membrane\Exception\CannotProcessProperty
 * @uses \Membrane\Attribute\FilterOrValidator
 * @uses \Membrane\Attribute\SetFilterOrValidator
 * @uses \Membrane\Attribute\OverrideProcessorType
 * @uses \Membrane\Attribute\Subtype
 * @uses \Membrane\Result\Result
 * @uses \Membrane\Result\MessageSet
 * @uses \Membrane\Result\Message
 * @uses \Membrane\Result\FieldName
 * @uses \Membrane\Processor\Collection
 * @uses \Membrane\Processor\FieldSet
 * @uses \Membrane\Processor\Field
 * @uses \Membrane\Processor\BeforeSet
 * @uses \Membrane\Processor\AfterSet
 * @uses \Membrane\Validator\Object\RequiredFields
 * @uses \Membrane\Validator\Type\IsList
 * @uses \Membrane\Validator\Type\IsInt
 * @uses \Membrane\Validator\Type\IsString
 * @uses \Membrane\Filter\Type\ToString
 * @uses \Membrane\Validator\String\Length
 * @uses \Membrane\Validator\String\Regex
 * @uses \Membrane\Validator\Utility\AllOf
 * @uses \Membrane\Validator\Array\Count
 * @uses \Membrane\Filter\CreateObject\WithNamedArguments
 */
class BuilderTest extends TestCase
{
    /**
     * @test
     */
    public function passingNonExistentClassNameToFromClassThrowsException(): void
    {
        $builder = new Builder();
        self::expectException('Exception');
        self::expectExceptionMessage('Could not find class NotAClass');

        $builder->fromClass('NotAClass');
    }

    /**
     * @test
     */
    public function noTypeHintThrowsException(): void
    {
        $builder = new Builder();

        self::expectException(CannotProcessProperty::class);
        self::expectExceptionMessage('Property undefinedProperty does not define it\'s type');

        $builder->fromClass(ClassWithNoTypeHint::class);
    }

    /**
     * @test
     */
    public function noSubTypeHintThrowsException(): void
    {
        $builder = new Builder();

        self::expectException(CannotProcessProperty::class);
        self::expectExceptionMessage('Property arrayOfMystery is a collection but does not define it\'s subtype');

        $builder->fromClass(ClassWithNoSubTypeHint::class);
    }

    /**
     * @test
     */
    public function compoundPropertyThrowsException(): void
    {
        $builder = new Builder();

        self::expectException(CannotProcessProperty::class);
        self::expectExceptionMessage(
            'Property compoundProperty uses a compound type hint, these are not currently supported'
        );

        $builder->fromClass(ClassWithCompoundPropertyType::class);
    }

    /**
     * @test
     */
    public function nestedCollectionThrowsException(): void
    {
        $builder = new Builder();

        self::expectException(CannotProcessProperty::class);
        self::expectExceptionMessage(
            'Property arrayOfArrays is a collection and defines it\'s subtype as array. ' .
            'Nested collections are not currently supported'
        );

        $builder->fromClass(ClassWithNestedCollection::class);
    }

    public function dataSetOfClassesToBuild(): array
    {
        return [
            EmptyClass::class => [
            EmptyClass::class,
            new FieldSet('')
            ],
            EmptyClassWithIgnoredProperty::class => [
                EmptyClassWithIgnoredProperty::class,
                new FieldSet('')
            ],
            ClassWithDateTimeProperty::class => [
                ClassWithDateTimeProperty::class,
                new FieldSet('', new Field('dateTime'))
            ],
            ClassWithIntProperty::class => [
              ClassWithIntProperty::class,
              new FieldSet('', new Field('integerProperty'))
            ],
            ClassWithIntPropertyIgnoredProperty::class => [
                ClassWithIntPropertyIgnoredProperty::class,
                new FieldSet('', new Field('integerProperty'))
            ],
            ClassWithIntPropertyIsIntValidator::class => [
                ClassWithIntPropertyIsIntValidator::class,
                new FieldSet('', new Field('integerProperty', new IsInt()))
            ],
            ClassWithIntArrayPropertyIsIntValidator::class => [
                ClassWithIntArrayPropertyIsIntValidator::class,
                new FieldSet('', new Collection(
                    'arrayOfInts',
                    new Field('arrayOfInts', new IsInt())
                    )
                )
            ],
            ClassWithClassArrayPropertyIsIntValidator::class => [
                ClassWithClassArrayPropertyIsIntValidator::class,
                new FieldSet('', new Collection(
                        'arrayOfClasses',
                        new FieldSet('arrayOfClasses', new Field('integerProperty', new IsInt()))
                    )
                )
            ],
            ClassWithClassProperty::class => [
                ClassWithClassProperty::class,
                new FieldSet('', new FieldSet(
                        'class',
                        new Field('integerProperty', new IsInt())
                    )
                )
            ],
            ClassWithStringPropertyBeforeSet::class => [
                ClassWithStringPropertyBeforeSet::class,
                new FieldSet('', new Field('property'), new BeforeSet(new RequiredFields('property')))
            ],
            ClassWithIntArrayPropertyBeforeSet::class => [
                ClassWithIntArrayPropertyBeforeSet::class,
                new FieldSet('', new Collection(
                        'arrayOfInts',
                        new BeforeSet(new IsList()),
                        new Field('arrayOfInts', new IsInt())
                    )
                )
            ],
            ClassWithPromotedPropertyAfterSet::class => [
                ClassWithPromotedPropertyAfterSet::class,
                new FieldSet(
                    '',
                    new Field('promotedProperty', new IsInt()),
                    new AfterSet(new WithNamedArguments(ClassWithPromotedPropertyAfterSet::class))
                )
            ],
            ClassThatOverridesProcessorType::class => [
                ClassThatOverridesProcessorType::class,
                new FieldSet('', new Collection(
                    'sumOfInts',
                    new BeforeSet(new IsList()),
                    new Field('sumOfInts', new IsInt()),
                    new AfterSet(new ArraySumFilter())
                ))
            ],

        ];
    }

    /**
     * @test
     * @dataProvider dataSetOfClassesToBuild
     */
    public function BuildingProcessorsTest(string $className, FieldSet $expected):void
    {
        $builder = new Builder();

        $output = $builder->fromClass($className);

        self::assertEquals($expected, $output);
    }

    public function dataSetOfInputsAndOutputs(): array
    {
        return [
            EmptyClass::class => [
                EmptyClass::class,
                [],
                Result::noResult([]),
            ],
            EmptyClassWithIgnoredProperty::class => [
                EmptyClassWithIgnoredProperty::class,
                [],
                Result::noResult([]),
            ],
            ClassWithDateTimeProperty::class => [
                ClassWithDateTimeProperty::class,
                [],
                Result::noResult([])
            ],
            ClassWithIntProperty::class => [
                ClassWithIntProperty::class,
                ['a' => 1, 'b' => 2 , 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2 , 'c' => 3]),
            ],
            ClassWithIntPropertyIgnoredProperty::class => [
                ClassWithIntPropertyIgnoredProperty::class,
                ['a' => 1, 'b' => 2 , 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2 , 'c' => 3]),
            ],
            ClassWithIntPropertyIsIntValidator::class => [
                ClassWithIntPropertyIsIntValidator::class,
                ['a' => 1, 'b' => 2 , 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2 , 'c' => 3]),
            ],
            ClassWithIntArrayPropertyIsIntValidator::class => [
                ClassWithIntArrayPropertyIsIntValidator::class,
                ['a' => 1, 'b' => 2 , 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2 , 'c' => 3]),
            ],
            ClassWithClassArrayPropertyIsIntValidator::class => [
                ClassWithClassArrayPropertyIsIntValidator::class,
                ['a' => 1, 'b' => 2 , 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2 , 'c' => 3]),
            ],
            ClassWithClassProperty::class => [
                ClassWithClassProperty::class,
                ['a' => 1, 'b' => 2 , 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2 , 'c' => 3]),
            ],
            ClassWithStringPropertyBeforeSet::class => [
                ClassWithStringPropertyBeforeSet::class,
                ['property' => 1],
                Result::valid(['property' => 1]),
            ],
            ClassWithIntArrayPropertyBeforeSet::class => [
                ClassWithIntArrayPropertyBeforeSet::class,
                ['a' => 1, 'b' => 2 , 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2 , 'c' => 3]),
            ],
            ClassWithPromotedPropertyAfterSet::class => [
                ClassWithPromotedPropertyAfterSet::class,
                ['promotedProperty' => 1],
                Result::valid(new ClassWithPromotedPropertyAfterSet(1)),
            ],
            ClassThatOverridesProcessorType::class => [
                ClassThatOverridesProcessorType::class,
                ['a' => 1, 'b' => 2 , 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2 , 'c' => 3]),
            ],

        ];
    }

    /**
     * @test
     * @dataProvider dataSetOfInputsAndOutputs
     */
    public function InputsAndOutputsTest(string $className, mixed $input, mixed $expected):void
    {
        $builder = new Builder();
        $processor = $builder->fromClass($className);

        $output = $processor->process(new FieldName(''), $input);

        self::assertEquals($expected, $output);
    }

    public function dataSetsWithDocExamples(): array
    {
        return [
            'Blog Post: Required Fields A' => [
                BlogPostRequiredFields::class,
                ['title' => 'My Post', 'body' => 'My content'],
                Result::valid(['title' => 'My Post', 'body' => 'My content'])
            ],
            'Blog Post: Required Fields B' => [
                BlogPostRequiredFields::class,
                ['title' => 123, 'body' => ''],
                Result::valid(['title' => 123, 'body' => ''])
            ],
            'Blog Post: Required Fields C' => [
                BlogPostRequiredFields::class,
                ['title' => 'My Post'],
                Result::invalid(
                    ['title' => 'My Post'],
                    new MessageSet(new FieldName('', '', ''), new Message('%s is a required field', ['body']))
                )
            ],
            'Blog Post: Is It A String? A' => [
                BlogPostIsItAString::class,
                [
                    'title' => 'My Post',
                    'body' => 'My content',
                    'tags' => ['tag1', 'tag2'],
                ],
                Result::valid(
                    [
                        'title' => 'My Post',
                        'body' => 'My content',
                        'tags' => ['tag1', 'tag2'],
                    ]
                )
            ],
            'Blog Post: Is It A String? B' => [
                BlogPostIsItAString::class,
                [
                    'title' => 123,
                    'body' => 'My content',
                    'tags' => ['tag1', 'tag2'],
                ],
                Result::invalid(
                    [
                        'title' => 123,
                        'body' => 'My content',
                        'tags' => ['tag1', 'tag2'],
                    ],
                    new MessageSet(new FieldName('title', '', ''), new Message(
                        'Value passed to IsString validator is not a string, %s passed instead',
                        ['integer']
                    ))
                )
            ],
            'Blog Post: Make It A String A' => [
                BlogPostMakeItAString::class,
                [
                    'title' => 123,
                    'body' => 'My content',
                    'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6', 'tag7'],
                ],
                Result::valid(
                    [
                        'title' => '123',
                        'body' => 'My content',
                        'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6', 'tag7'],
                    ]
                )
            ],
            'Blog Post: Make It A String B' => [
                BlogPostMakeItAString::class,
                [
                    'title' => [1, 2, 3],
                    'body' => 'My content',
                    'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6', 'tag7'],
                ],
                Result::invalid(
                    [
                        'title' => [1, 2, 3],
                        'body' => 'My content',
                        'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6', 'tag7'],
                    ],
                    new MessageSet(new FieldName('title', '', ''), new Message(
                        'ToString filter only accepts objects, null or scalar values, %s given',
                        ['array']
                    ))
                )
            ],
            'Blog Post: Maximum Number Of Tags A' => [
                BlogPostMaxTags::class,
                [
                    'title' => '',
                    'body' => 'My content',
                    'tags' => ['tag1', 'tag2', 'tag3', 'tag4'],
                ],
                Result::valid(
                    [
                        'title' => '',
                        'body' => 'My content',
                        'tags' => ['tag1', 'tag2', 'tag3', 'tag4'],
                    ]
                )
            ],
            'Blog Post: Maximum Number Of Tags B' => [
                BlogPostMaxTags::class,
                [
                    'title' => 'TITLE WRITTEN ENTIRELY IN UPPER CASE AND UNNECESSARILY LONG',
                    'body' => 'My content',
                    'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6', 'tag7']
                ],
                Result::invalid(
                    [
                        'title' => 'TITLE WRITTEN ENTIRELY IN UPPER CASE AND UNNECESSARILY LONG',
                        'body' => 'My content',
                        'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6', 'tag7']
                    ],
                    new MessageSet(new FieldName('', '', '', 'tags'), new Message(
                        'Array is expected have a maximum of %d values',
                        [5]
                    ))
                )
            ],
            'Blog Post: Regex And Max Length A' => [
                BlogPostRegexAndMaxLength::class,
                [
                    'title' => 'Title With Proper Capitalization',
                    'body' => 'My content',
                    'tags' => ['tag1', 'tag2', 'tag3', 'tag4'],
                ],
                Result::valid(
                    [
                        'title' => 'Title With Proper Capitalization',
                        'body' => 'My content',
                        'tags' => ['tag1', 'tag2', 'tag3', 'tag4'],
                    ]
                )
            ],
            'Blog Post: Regex And Max Length B' => [
                BlogPostRegexAndMaxLength::class,
                [
                    'title' => 'TITLE WRITTEN ENTIRELY IN UPPER CASE AND UNNECESSARILY LONG',
                    'body' => 'My content',
                    'tags' => ['tag1', 'tag2', 'tag3', 'tag4']
                ],
                Result::invalid(
                    [
                        'title' => 'TITLE WRITTEN ENTIRELY IN UPPER CASE AND UNNECESSARILY LONG',
                        'body' => 'My content',
                        'tags' => ['tag1', 'tag2', 'tag3', 'tag4']
                    ],
                    new MessageSet(new FieldName('title', '', ''), new Message(
                        'String is expected to be a maximum of %d characters',
                        [50]
                    ))
                )
            ],
            'Blog Post: All Of A' => [
                BlogPostWithAllOf::class,
                [
                    'title' => 'Title With Proper Capitalization',
                    'body' => 'My content',
                    'tags' => ['tag1', 'tag2', 'tag3', 'tag4'],
                ],
                Result::valid(
                    [
                        'title' => 'Title With Proper Capitalization',
                        'body' => 'My content',
                        'tags' => ['tag1', 'tag2', 'tag3', 'tag4'],
                    ]
                )
            ],
            'Blog Post: All Of B' => [
                BlogPostWithAllOf::class,
                [
                    'title' => 'TITLE WRITTEN ENTIRELY IN UPPER CASE AND UNNECESSARILY LONG',
                    'body' => 'My content',
                    'tags' => ['tag1', 'tag2', 'tag3', 'tag4']
                ],
                Result::invalid(
                    [
                        'title' => 'TITLE WRITTEN ENTIRELY IN UPPER CASE AND UNNECESSARILY LONG',
                        'body' => 'My content',
                        'tags' => ['tag1', 'tag2', 'tag3', 'tag4']
                    ],
                    new MessageSet(new FieldName('title', '', ''),
                        new Message(
                            'String is expected to be a maximum of %d characters',
                            [50]
                        ),
                        new Message(
                            'String does not match the required pattern %s',
                            ['#^([A-Z][a-z]*\s){0,9}([A-Z][a-z]*)$#']
                        )
                    ),
                )
            ],
            'Blog Post: Build Your Blog Post From Named Arguments' => [
                BlogPostFromNamedArguments::class,
                [
                    'title' => 'Title With Proper Capitalization',
                    'body' => 'My content',
                    'tags' => ['tag1', 'tag2', 'tag3', 'tag4'],
                ],
                Result::valid(new BlogPostFromNamedArguments(
                    'Title With Proper Capitalization',
                    'My content',
                    ['tag1', 'tag2', 'tag3', 'tag4'],
                ))
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithDocExamples
     */
    public function docExamplesTest(string $className, array $input, Result $expected): void
    {
        $builder = new Builder();
        $processor = $builder->fromClass($className);

        $result = $processor->process(new FieldName(''), $input);

        self::assertEquals($expected, $result);
    }

}
