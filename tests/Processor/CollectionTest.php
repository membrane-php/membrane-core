<?php

declare(strict_types=1);

namespace Processor;

use Membrane\Exception\InvalidProcessorArguments;
use Membrane\Filter;
use Membrane\Processor;
use Membrane\Processor\AfterSet;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Processor\Field;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Processor\Collection
 * @covers \Membrane\Exception\InvalidProcessorArguments
 * @uses   \Membrane\Processor\BeforeSet
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Processor\AfterSet
 * @uses   \Membrane\Result\FieldName
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
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

    /**
     * @test
     */
    public function onlyAcceptsOneField(): void
    {
        $field = new Field('field to process');
        self::expectExceptionObject(InvalidProcessorArguments::multipleProcessorsInCollection());

        new Collection('field to process', $field, $field);
    }

    /**
     * @test
     */
    public function processesTest(): void
    {
        $fieldName = 'field to process';
        $fieldset = new Collection($fieldName);

        $output = $fieldset->processes();

        self::assertEquals($fieldName, $output);
    }

    /**
     * @test
     */
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
        $filter1To2 = self::createMock(Filter::class);
        $filter1To2->method('filter')
            ->with(1)
            ->willReturn(Result::noResult(2));

        $filter2To3 = self::createMock(Filter::class);
        $filter2To3->method('filter')
            ->with(2)
            ->willReturn(Result::noResult(3));

        $filter123ArrayTo321List = self::createMock(Filter::class);
        $filter123ArrayTo321List->method('filter')
            ->with([1, 2, 3])
            ->willReturn(Result::noResult([3, 2, 1]));

        $validate111List = self::createMock(Filter::class);
        $validate111List->method('filter')
            ->with([1, 1, 1])
            ->willReturn(Result::valid([1, 1, 1]));

        $validate222List = self::createMock(Filter::class);
        $validate222List->method('filter')
            ->with([2, 2, 2])
            ->willReturn(Result::valid([2, 2, 2]));

        $validate321List = self::createMock(Filter::class);
        $validate321List->method('filter')
            ->with([3, 2, 1])
            ->willReturn(Result::valid([3, 2, 1]));

        $invalidate123List = self::createMock(Filter::class);
        $invalidate123List->method('filter')
            ->with([1, 2, 3])
            ->willReturn(Result::invalid([1, 2, 3], new MessageSet(null, new Message('oh no!', []))));

        $validate1 = self::createMock(Validator::class);
        $validate1->method('validate')
            ->with(1)
            ->willReturn(Result::valid(1));

        $validate2 = self::createMock(Validator::class);
        $validate2->method('validate')
            ->with(2)
            ->willReturn(Result::valid(2));

        $invalidate1 = self::createMock(Validator::class);
        $invalidate1->method('validate')
            ->with(1)
            ->willReturn(Result::invalid(1, new MessageSet(null, new Message('oh no!', []))));

        return [
            'Field process method is called for every item in array' => [
                [1, 1, 1],
                Result::noResult([2, 2, 2]),
                new Field('a', $filter1To2),
            ],
            'Field processed values persist' => [
                [1, 1, 1],
                Result::noResult([3, 3, 3]),
                new Field('b', $filter1To2, $filter2To3),
            ],
            'Field processed can return valid results' => [
                [1, 1, 1],
                Result::valid([2, 2, 2]),
                new Field('c', $filter1To2, $validate2),
            ],
            'Field processed can return invalid results' => [
                [1, 1, 1],
                Result::invalid(
                    [1, 1, 1],
                    new MessageSet(
                        new FieldName('d', 'parent field', 'field to process', '0'),
                        new Message('oh no!', [])
                    ),
                    new MessageSet(
                        new FieldName('d', 'parent field', 'field to process', '1'),
                        new Message('oh no!', [])
                    ),
                    new MessageSet(
                        new FieldName('d', 'parent field', 'field to process', '2'),
                        new Message('oh no!', [])
                    )
                ),
                new Field('d', $invalidate1),
            ],
            'BeforeSet processes before Field' => [
                [1, 1, 1],
                Result::valid([2, 2, 2]),
                new BeforeSet($validate111List),
                new Field('e', $filter1To2),
            ],
            'BeforeSet processes before AfterSet' => [
                [1, 2, 3],
                Result::valid([3, 2, 1]),
                new BeforeSet($filter123ArrayTo321List),
                new AfterSet($validate321List),
            ],
            'AfterSet does not process if BeforeSet returns invalid' => [
                [1, 2, 3],
                Result::invalid(
                    [1, 2, 3],
                    new MessageSet(new FieldName('', 'parent field', 'field to process'), new Message('oh no!', []))
                ),
                new BeforeSet($invalidate123List),
                new AfterSet($filter123ArrayTo321List),
            ],
            'AfterSet processes after Field' => [
                [1, 1, 1],
                Result::valid([2, 2, 2],),
                new Field('a', $filter1To2),
                new AfterSet($validate222List),
            ],
            'BeforeSet then Field then AfterSet' => [
                [1, 1, 1],
                Result::valid([2, 2, 2]),
                new BeforeSet($validate111List),
                new Field('b', $filter1To2),
                new AfterSet($validate222List),
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
    }
}
