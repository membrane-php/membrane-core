<?php

declare(strict_types=1);

namespace Attribute;

use Membrane\Attribute\Builder;
use Membrane\Fixtures\ClassWithClassArrayPropertyIsIntValidator;
use Membrane\Fixtures\ClassWithIntArrayPropertyIsIntValidator;
use Membrane\Fixtures\ClassWithIntProperty;
use Membrane\Fixtures\ClassWithIntPropertyIsIntValidator;
use Membrane\Fixtures\EmptyClass;
use Membrane\Processor\Collection;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Validator\Type\IsInt;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Attribute\Builder
 * @uses \Membrane\Attribute\FilterOrValidator
 * @uses \Membrane\Attribute\Subtype
 * @uses \Membrane\Processor\Collection
 * @uses \Membrane\Processor\FieldSet
 * @uses \Membrane\Processor\Field
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

    public function dataSetOfClassesToBuild(): array
    {
        return [
            EmptyClass::class => [
            EmptyClass::class,
            new FieldSet('')
            ],
            ClassWithIntProperty::class => [
              ClassWithIntProperty::class,
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
        ];
    }

    /**
     * @test
     * @dataProvider dataSetOfClassesToBuild
     */
    public function test(string $className, FieldSet $expected):void
    {
        $builder = new Builder();

        $output = $builder->fromClass($className);

        self::assertEquals($expected, $output);
    }
}
