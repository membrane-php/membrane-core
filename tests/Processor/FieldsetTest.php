<?php

declare(strict_types=1);

namespace Processor;

use Membrane\Exception\InvalidProcessorArguments;
use Membrane\Filter;
use Membrane\Processor;
use Membrane\Processor\AfterSet;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\DefaultProcessor;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Processor\FieldSet
 * @covers \Membrane\Exception\InvalidProcessorArguments
 * @uses   \Membrane\Processor\AfterSet
 * @uses   \Membrane\Processor\BeforeSet
 * @uses   \Membrane\Processor\DefaultProcessor
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Result\FieldName
 */
class FieldsetTest extends TestCase
{
    public function dataSetsWithIncorrectValues(): array
    {
        $notArrayMessage = 'Value passed to FieldSet chain be an array, %s passed instead';
        $listMessage = 'Value passed to FieldSet chain must be an array, list passed instead';
        return [
            [1, new Message($notArrayMessage, ['integer'])],
            [2.0, new Message($notArrayMessage, ['double'])],
            ['string', new Message($notArrayMessage, ['string'])],
            [true, new Message($notArrayMessage, ['boolean'])],
            [null, new Message($notArrayMessage, ['NULL'])],
            [['a', 'b', 'c'], new Message($listMessage, [])],
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
        $fieldset = new FieldSet($fieldName, new Field(''));

        $result = $fieldset->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $result);
    }

    /** @test */
    public function onlyAcceptsOneBeforeSet(): void
    {
        $beforeSet = new BeforeSet();
        self::expectExceptionObject(InvalidProcessorArguments::multipleBeforeSetsInFieldSet());

        new FieldSet('field to process', $beforeSet, $beforeSet);
    }

    /** @test */
    public function onlyAcceptsOneAfterSet(): void
    {
        $afterSet = new AfterSet();
        self::expectExceptionObject(InvalidProcessorArguments::multipleAfterSetsInFieldSet());

        new FieldSet('field to process', $afterSet, $afterSet);
    }

    /** @test */
    public function onlyAcceptsOneDefaultField(): void
    {
        $defaultField = DefaultProcessor::fromFiltersAndValidators();
        self::expectExceptionObject(InvalidProcessorArguments::multipleDefaultProcessorsInFieldSet());

        new FieldSet('field to process', $defaultField, $defaultField);
    }

    /** @test */
    public function processesTest(): void
    {
        $fieldName = 'field to process';
        $fieldset = new FieldSet($fieldName);

        $output = $fieldset->processes();

        self::assertEquals($fieldName, $output);
    }

    /** @test */
    public function processMethodWithNoChainReturnsNoResult(): void
    {
        $value = [];
        $expected = Result::noResult($value);
        $fieldset = new FieldSet('field to process');

        $result = $fieldset->process(new FieldName('Parent field'), $value);

        self::assertEquals($expected, $result);
    }

    /** @test */
    public function processMethodCallsFieldProcessesMethod(): void
    {
        $input = ['a' => 1, 'b' => 2, 'c' => 3];
        $field = self::createMock(Field::class);
        $field->expects(self::once())
            ->method('processes');
        $fieldset = new FieldSet('field to process', $field);

        $fieldset->process(new FieldName('Parent field'), $input);
    }

    /** @test */
    public function processCallsBeforeSetProcessOnceAndProcessesNever(): void
    {
        $input = ['a' => 1, 'b' => 2, 'c' => 3];
        $beforeSet = self::createMock(BeforeSet::class);
        $beforeSet->expects(self::never())
            ->method('processes');
        $beforeSet->expects(self::once())
            ->method('process')
            ->willReturn(Result::invalid($input));

        $fieldset = new FieldSet('field to process', $beforeSet);

        $fieldset->process(new FieldName('Parent field'), $input);
    }

    /** @test */
    public function processCallsAfterSetProcessOnceAndProcessesNever(): void
    {
        $input = ['a' => 1, 'b' => 2, 'c' => 3];
        $afterSet = self::createMock(AfterSet::class);
        $afterSet->expects(self::never())
            ->method('processes');
        $afterSet->expects(self::once())
            ->method('process')
            ->willReturn(Result::invalid($input));

        $fieldset = new FieldSet('field to process', $afterSet);

        $fieldset->process(new FieldName('Parent field'), $input);
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

        $filter123ArrayTo321Array = self::createMock(Filter::class);
        $filter123ArrayTo321Array->method('filter')
            ->with(['a' => 1, 'b' => 2, 'c' => 3])
            ->willReturn(Result::noResult(['a' => 3, 'b' => 2, 'c' => 1]));

        $validate111Array = self::createMock(Filter::class);
        $validate111Array->method('filter')
            ->with(['a' => 1, 'b' => 1, 'c' => 1])
            ->willReturn(Result::valid(['a' => 1, 'b' => 1, 'c' => 1]));

        $validate321Array = self::createMock(Filter::class);
        $validate321Array->method('filter')
            ->with(['a' => 3, 'b' => 2, 'c' => 1])
            ->willReturn(Result::valid(['a' => 3, 'b' => 2, 'c' => 1]));

        $validate1 = self::createMock(Validator::class);
        $validate1->method('validate')
            ->with(1)
            ->willReturn(Result::valid(1));

        $validate2 = self::createMock(Validator::class);
        $validate2->method('validate')
            ->with(2)
            ->willReturn(Result::valid(2));

        $invalidate3 = self::createMock(Validator::class);
        $invalidate3->method('validate')
            ->with(3)
            ->willReturn(Result::invalid(3, new MessageSet(null, new Message('oh no!', []))));

        return [
            'Field only performs processes on defined processes field' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 2, 'b' => 2, 'c' => 3]),
                new Field('a', $filter1To2),
            ],
            'DefaultProcessor only processes fields not processed by other Field Processors' => [
                ['a' => 1, 'b' => 2, 'c' => 2],
                Result::noResult(['a' => 2, 'b' => 3, 'c' => 3]),
                new Field('a', $filter1To2),
                DefaultProcessor::fromFiltersAndValidators($filter2To3),
            ],
            'Field processed values persist' => [
                ['a' => 1, 'b' => 1, 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 3, 'c' => 3]),
                new Field('b', $filter1To2, $filter2To3),
            ],
            'Field processed can return valid results' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 1, 'b' => 2, 'c' => 3]),
                new Field('b', $validate2),
            ],
            'Field processed can return invalid results' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::invalid(['a' => 1, 'b' => 2, 'c' => 3],
                    new MessageSet(
                        new FieldName('c', 'parent field', 'field to process'),
                        new Message('oh no!', [])
                    )),
                new Field('c', $invalidate3),
            ],
            'Multiple Fields are accepted' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 2, 'b' => 3, 'c' => 3]),
                new Field('a', $filter1To2),
                new Field('a', $validate2),
                new Field('b', $filter2To3),
            ],
            'BeforeSetProcessesBeforeField' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 3, 'b' => 2, 'c' => 1]),
                new BeforeSet($filter123ArrayTo321Array),
                new Field('c', $validate1),
            ],
            'BeforeSetProcessesBeforeAfterSet' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 3, 'b' => 2, 'c' => 1]),
                new BeforeSet($filter123ArrayTo321Array),
                new AfterSet($validate321Array),
            ],
            'AfterSetProcessesAfterField' => [
                ['a' => 2, 'b' => 1, 'c' => 1],
                Result::valid(['a' => 3, 'b' => 2, 'c' => 1]),
                new Field('a', $filter2To3),
                new Field('b', $filter1To2),
                new AfterSet($validate321Array),
            ],
            'BeforeSetThenFieldThenAfterSet' => [
                ['a' => 1, 'b' => 1, 'c' => 1],
                Result::valid(['a' => 3, 'b' => 2, 'c' => 1]),
                new BeforeSet($validate111Array),
                new Field('a', $filter1To2),
                new Field('a', $filter2To3),
                new Field('b', $filter1To2),
                new AfterSet($validate321Array),
            ],
        ];
    }


    /**
     * @test
     * @dataProvider dataSetsOfFields
     */
    public function processTest(array $input, Result $expected, Processor ...$chain): void
    {
        $fieldset = new FieldSet('field to process', ...$chain);

        $result = $fieldset->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $result);
    }
}
