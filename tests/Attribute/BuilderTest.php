<?php

declare(strict_types=1);

namespace Attribute;

use Membrane\Attribute\Builder;
use Membrane\Fixtures\ClassWithOnePropertyNoAttributes;
use Membrane\Fixtures\EmptyClass;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Attribute\Builder
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
            ClassWithOnePropertyNoAttributes::class => [
              ClassWithOnePropertyNoAttributes::class,
              new FieldSet('', new Field('integerProperty'))
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
