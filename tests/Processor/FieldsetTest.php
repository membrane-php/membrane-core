<?php

declare(strict_types=1);

namespace Processor;

use Membrane\Exception\InvalidProcessorArguments;
use Membrane\Filter;
use Membrane\Processor;
use Membrane\Processor\AfterSet;
use Membrane\Processor\BeforeSet;
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

    /**
     * @test
     */
    public function onlyAcceptsOneBeforeSet(): void
    {
        $beforeSet = new BeforeSet();
        self::expectExceptionObject(InvalidProcessorArguments::multipleBeforeSetsInFieldSet());

        new FieldSet('field to process', $beforeSet, $beforeSet);
    }

    /**
     * @test
     */
    public function onlyAcceptsOneAfterSet(): void
    {
        $afterSet = new AfterSet();
        self::expectExceptionObject(InvalidProcessorArguments::multipleAfterSetsInFieldSet());

        new FieldSet('field to process', $afterSet, $afterSet);
    }

    /**
     * @test
     */
    public function processesTest(): void
    {
        $fieldName = 'field to process';
        $fieldset = new FieldSet($fieldName);

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
        $fieldset = new FieldSet('field to process');

        $result = $fieldset->process(new FieldName('Parent field'), $value);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function processMethodCallsFieldProcessesMethod(): void
    {
        $input = ['a' => 1, 'b' => 2, 'c' => 3];
        $field = self::createMock(Field::class);
        $field->expects(self::once())
            ->method('processes');
        $fieldset = new FieldSet('field to process', $field);

        $fieldset->process(new FieldName('Parent field'), $input);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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
        $incrementFilter = new class implements Filter {
            public function filter(mixed $value): Result
            {
                return Result::noResult(++$value);
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
                    return Result::invalid(
                        $value,
                        new MessageSet(
                            null,
                            new Message('not even', [])
                        )
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
                        return Result::invalid(
                            $value,
                            new MessageSet(
                                null,
                                new Message('not even', [])
                            )
                        );
                    }
                }
                return Result::valid($value);
            }
        };

        return [
            'Field only performs processes on defined processes field' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 2, 'b' => 2, 'c' => 3]),
                new Field('a', $incrementFilter),
            ],
            'Field processed values persist' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 4, 'c' => 3]),
                new Field('b', $incrementFilter, $incrementFilter),
            ],
            'Field processed can return valid results' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 1, 'b' => 2, 'c' => 3]),
                new Field('b', $evenValidator),
            ],
            'Field processed can return invalid results' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::invalid(['a' => 1, 'b' => 2, 'c' => 3],
                    new MessageSet(
                        new FieldName('a', 'parent field', 'field to process'),
                        new Message('not even', [])
                    )),
                new Field('a', $evenValidator),
            ],
            'Multiple Fields are accepted' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 2, 'b' => 3, 'c' => 3]),
                new Field('a', $incrementFilter),
                new Field('a', $evenValidator),
                new Field('b', $incrementFilter),
            ],
            'BeforeSetProcessesBeforeField' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 2, 'b' => 4, 'c' => 6]),
                new BeforeSet($evenArrayFilter),
                new Field('c', $evenValidator),
            ],
            'BeforeSetProcessesBeforeAfterSet' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 2, 'b' => 4, 'c' => 6]),
                new BeforeSet($evenArrayFilter),
                new AfterSet($evenArrayValidator),
            ],
            'AfterSetProcessesAfterField' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 2, 'b' => 2, 'c' => 4]),
                new Field('a', $incrementFilter),
                new Field('c', $incrementFilter),
                new AfterSet($evenArrayValidator),
            ],
            'BeforeSetThenFieldThenAfterSet' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::invalid(['a' => 2, 'b' => 5, 'c' => 6],
                    new MessageSet(
                        new FieldName('', 'parent field', 'field to process'),
                        new Message('not even', [])
                    )),
                new BeforeSet($evenArrayFilter),
                new Field('b', $incrementFilter),
                new AfterSet($evenArrayValidator),
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
