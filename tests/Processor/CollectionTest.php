<?php

declare(strict_types=1);

namespace Processor;

use Membrane\Exception\InvalidProcessorArguments;
use Membrane\Filter\Shape\Truncate;
use Membrane\Filter\Type\ToFloat;
use Membrane\Processor;
use Membrane\Processor\AfterSet;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Processor\Field;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Collection\Count;
use Membrane\Validator\Type\IsFloat;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Processor\Collection
 * @covers \Membrane\Exception\InvalidProcessorArguments
 * @uses   \Membrane\Filter\Shape\Truncate
 * @uses   \Membrane\Filter\Type\ToFloat
 * @uses   \Membrane\Processor\BeforeSet
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Processor\AfterSet
 * @uses   \Membrane\Result\FieldName
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Validator\Collection\Count
 * @uses   \Membrane\Validator\Type\IsFloat
 * @uses   \Membrane\Validator\Utility\Passes
 * @uses   \Membrane\Validator\Utility\Indifferent
 * @uses   \Membrane\Validator\Utility\Fails
 */
class CollectionTest extends TestCase
{
    public function dataSetsWithIncorrectValues(): array
    {
        $notArrayMessage = 'Value passed to %s in Collection chain must be a list, %s passed instead';
        return [
            [1, new Message($notArrayMessage, ['Membrane\Processor\Field', 'integer'])],
            [2.0, new Message($notArrayMessage, ['Membrane\Processor\Field', 'double'])],
            ['string', new Message($notArrayMessage, ['Membrane\Processor\Field', 'string'])],
            [true, new Message($notArrayMessage, ['Membrane\Processor\Field', 'boolean'])],
            [null, new Message($notArrayMessage, ['Membrane\Processor\Field', 'NULL'])],
            [['a' => 1, 'b' => 2, 'c' => 3], new Message($notArrayMessage, ['Membrane\Processor\Field', 'array'])],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithIncorrectValues
     */
    public function onlyAcceptsArrayValues(mixed $input, Message $expectedMessage): void
    {
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $fieldName = 'field to process';
        $fieldset = new Collection($fieldName, new Field(''));

        $result = $fieldset->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $result);
    }

    /** @test */
    public function onlyAcceptsOneField(): void
    {
        $field = new Field('field to process');
        self::expectExceptionObject(InvalidProcessorArguments::multipleProcessorsInCollection());

        new Collection('field to process', $field, $field);
    }

    /** @test */
    public function processesTest(): void
    {
        $fieldName = 'field to process';
        $fieldset = new Collection($fieldName);

        $output = $fieldset->processes();

        self::assertEquals($fieldName, $output);
    }

    /** @test */
    public function processMethodWithNoChainReturnsNoResult(): void
    {
        $value = [];
        $expected = Result::noResult($value);
        $fieldset = new Collection('field to process');

        $result = $fieldset->process(new FieldName('Parent field'), $value);

        self::assertEquals($expected, $result);
    }

    public function dataSetsOfFields(): array
    {
        return [
            'No chain returns noResult' => [
                [1, 2, 3],
                Result::noResult([1, 2, 3]),
            ],
            'Return valid result' => [
                [1, 2, 3],
                Result::valid([1, 2, 3]),
                new Field('a', new Passes()),
            ],
            'Return noResult' => [
                [1, 2, 3],
                Result::noResult([1, 2, 3]),
                new Field('b', new Indifferent()),
            ],
            'Return invalid result' => [
                [1, 2, 3],
                Result::invalid(
                    [1, 2, 3],
                    new MessageSet(
                        new FieldName('c', 'parent field', 'field to process', '0'),
                        new Message('I always fail', [])
                    ),
                    new MessageSet(
                        new FieldName('c', 'parent field', 'field to process', '1'),
                        new Message('I always fail', [])
                    ),
                    new MessageSet(
                        new FieldName('c', 'parent field', 'field to process', '2'),
                        new Message('I always fail', [])
                    )
                ),
                new Field('c', new Fails()),
            ],
            'Field processes every item in array' => [
                [1, 2, 3],
                Result::noResult([1.0, 2.0, 3.0]),
                new Field('d', new ToFloat()),
            ],
            'Field processed values persist' => [
                [1, 2, 3],
                Result::valid([1.0, 2.0, 3.0]),
                new Field('e', new ToFloat(), new IsFloat()),
            ],
            'BeforeSet processes before Field' => [
                [1.0, 2.0, 3.0],
                Result::noResult([]),
                new BeforeSet(new Truncate(0)),
                new Field('f', new IsFloat()),
            ],
            'BeforeSet processes before AfterSet' => [
                [1, 2, 3],
                Result::invalid([1, 2],
                    new MessageSet(
                        new FieldName('', 'parent field', 'field to process'),
                        new Message('Array is expected have a minimum of %d values', [3])
                    )
                ),
                new BeforeSet(new Truncate(2)),
                new AfterSet(new Count(3)),
            ],
            'AfterSet does not process if BeforeSet returns invalid' => [
                [1, 2, 3],
                Result::invalid(
                    [1, 2, 3],
                    new MessageSet(
                        new FieldName('', 'parent field', 'field to process'),
                        new Message('Array is expected have a minimum of %d values', [4])
                    )
                ),
                new BeforeSet(new Count(4)),
                new AfterSet(new Truncate(2)),
            ],
            'AfterSet processes after Field' => [
                [1.0, 2.0, 3.0],
                Result::valid([]),
                new Field('i', new IsFloat()),
                new AfterSet(new Truncate(0)),
            ],
            'BeforeSet then Field then AfterSet' => [
                [1, 2, 3],
                Result::valid([]),
                new BeforeSet(new Truncate(0)),
                new Field('j', new IsFloat()),
                new AfterSet(new Count(0, 0)),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsOfFields
     */
    public function processTest(array $input, Result $expected, Processor ...$chain): void
    {
        $sut = new Collection('field to process', ...$chain);

        $actual = $sut->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $actual);
        self::assertSame($expected->value, $actual->value);
    }
}
