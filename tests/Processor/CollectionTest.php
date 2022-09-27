<?php

declare(strict_types=1);

namespace Processor;

use Membrane\Filter;
use Membrane\Processor;
use Membrane\Processor\AfterSet;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Processor\Field;
use Membrane\Result\Fieldname;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \Membrane\Processor\Collection
 * @uses   \Membrane\Processor\BeforeSet
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Processor\AfterSet
 * @uses   \Membrane\Result\Fieldname
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class CollectionTest extends TestCase
{
    public function DataSetsWithIncorrectValues(): array
    {
        $notArrayMessage = 'Value passed to Collection must be a list, %s passed instead';
        return [
            [1, new Message($notArrayMessage, ['integer'])],
            [2.0, new Message($notArrayMessage, ['double'])],
            ['string', new Message($notArrayMessage, ['string'])],
            [true, new Message($notArrayMessage, ['boolean'])],
            [null, new Message($notArrayMessage, ['NULL'])],
            [['a' => 1, 'b' => 2, 'c' => 3], new Message($notArrayMessage, ['array'])],
        ];
    }

    /**
     * @test
     * @dataProvider DataSetsWithIncorrectValues
     */
    public function OnlyAcceptsArrayValues(mixed $input, Message $expectedMessage): void
    {
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $fieldname = 'field to process';
        $fieldset = new Collection($fieldname);

        $result = $fieldset->process(new Fieldname('parent field'), $input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function OnlyAcceptsOneField(): void
    {
        $field = new Field('field to process');
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Cannot use more than one processor on a collection');

        new Collection('field to process', $field, $field);
    }

    /**
     * @test
     */
    public function ProcessesTest(): void
    {
        $fieldname = 'field to process';
        $fieldset = new Collection($fieldname);

        $output = $fieldset->processes();

        self::assertEquals($fieldname, $output);
    }

    /**
     * @test
     */
    public function ProcessMethodWithNoChainReturnsNoResult(): void
    {
        $value = [];
        $expected = Result::noResult($value);
        $fieldset = new Collection('field to process');

        $result = $fieldset->process(new Fieldname('Parent field'), $value);

        self::assertEquals($expected, $result);
    }

    public function DataSetsOfFields(): array
    {
        $incrementFilter = new class implements Filter {
            public function filter(mixed $value): Result
            {
                return Result::noResult(++$value);
            }
        };

        $evenFilter = new class implements Filter {
            public function filter(mixed $value): Result
            {
                return Result::noResult($value * 2);
            }
        };

        $evenArrayFilter = new class implements Filter {
            public function filter(mixed $value): Result
            {
                foreach (array_keys($value) as $key) {
                    $value[$key] *= 2;
                }
                return Result::noResult($value);
            }
        };

        $evenValidator = new class implements Validator {
            public function validate(mixed $value): Result
            {
                if ($value % 2 !== 0) {
                    return Result::invalid($value, new MessageSet(
                            null,
                            new Message('not even', []))
                    );
                }
                return Result::valid($value);
            }
        };

        $evenArrayValidator = new class implements Validator {
            public function validate(mixed $value): Result
            {
                foreach (array_keys($value) as $key) {
                    if ($value[$key] % 2 !== 0) {
                        return Result::invalid($value, new MessageSet(
                                null,
                                new Message('not even', []))
                        );
                    }
                }
                return Result::valid($value);
            }
        };

        return [
            'Field process method is called for every item in array' => [
                [1, 2, 3],
                Result::noResult([2, 3, 4]),
                new Field('a', $incrementFilter),
            ],
            'Field processed values persist' => [
                [1, 2, 3],
                Result::noResult([3, 4, 5]),
                new Field('b', $incrementFilter, $incrementFilter),
            ],
            'Field processed can return valid results' => [
                [1, 2, 3],
                Result::valid([2, 4, 6]),
                new Field('b', $evenFilter, $evenValidator),
            ],
            'Field processed can return invalid results' => [
                [1, 2, 3],
                Result::invalid([1, 2, 3],
                    new MessageSet(
                        new Fieldname('a', 'parent field', 'field to process', '0'),
                        new Message('not even', [])),
                    new MessageSet(
                        new Fieldname('a', 'parent field', 'field to process', '2'),
                        new Message('not even', []))),

                new Field('a', $evenValidator),
            ],
            'BeforeSet processes before Field' => [
                [1, 2, 3],
                Result::valid([2, 4, 6]),
                new BeforeSet($evenArrayFilter),
                new Field('c', $evenValidator),
            ],
            'BeforeSet processes before AfterSet' => [
                [1, 2, 3],
                Result::valid([2, 4, 6]),
                new BeforeSet($evenArrayFilter),
                new AfterSet($evenArrayValidator),
            ],
            'AfterSet does not process if BeforeSet returns invalid' => [
                [1, 2, 3],
                Result::invalid([1, 2, 3],
                    new MessageSet(
                        new Fieldname('', 'parent field', 'field to process'),
                        new Message('not even', []))),
                new BeforeSet($evenArrayValidator),
                new AfterSet($evenArrayFilter),
            ],
            'AfterSet processes after Field' => [
                [1, 2, 3],
                Result::invalid([2, 3, 4],
                    new MessageSet(
                        new Fieldname('', 'parent field', 'field to process'),
                        new Message('not even', []))),
                new Field('a', $incrementFilter),
                new AfterSet($evenArrayValidator),
            ],
            'BeforeSet then Field then AfterSet' => [
                [1, 2, 3],
                Result::invalid([3, 5, 7], new MessageSet(
                    new Fieldname('', 'parent field', 'field to process'),
                    new Message('not even', []))),
                new BeforeSet($evenArrayFilter),
                new Field('b', $incrementFilter),
                new AfterSet($evenArrayValidator),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider DataSetsOfFields
     */
    public function ProcessTest(array $input, Result $expected, Processor ...$chain): void
    {
        $fieldset = new Collection('field to process', ...$chain);

        $result = $fieldset->process(new Fieldname('parent field'), $input);

        self::assertEquals($expected, $result);
    }
}
