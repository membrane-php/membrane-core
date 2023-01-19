<?php

declare(strict_types=1);

namespace Processor;

use Membrane\Filter;
use Membrane\Processor\BeforeSet;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Processor\BeforeSet
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Validator\Utility\Fails
 * @uses   \Membrane\Validator\Utility\Indifferent
 * @uses   \Membrane\Validator\Utility\Passes
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Result\FieldName
 */
class BeforeSetTest extends TestCase
{
    /**
     * @test
     */
    public function processesMethodReturnsEmptyString(): void
    {
        $expected = '';
        $beforeSet = new BeforeSet();

        $result = $beforeSet->processes();

        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function noChainReturnsNoResult(): void
    {
        $input = ['a' => 1, 'b' => 2, 'c' => 3];
        $expected = Result::noResult($input);
        $field = new BeforeSet();

        $result = $field->process(new Fieldname('Parent FieldName'), $input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsForFiltersOrValidators(): array
    {
        $filter1To2 = self::createMock(Filter::class);
        $filter1To2->method('filter')
            ->with(1)
            ->willReturn(Result::noResult(2));

        $filter2To3 = self::createMock(Filter::class);
        $filter2To3->method('filter')
            ->with(2)
            ->willReturn(Result::noResult(3));

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
            'checks it can return valid' => [
                Result::valid(1),
                new BeforeSet(new Passes()),
                1,
            ],
            'checks it can return invalid' => [
                Result::invalid(
                    1,
                    new MessageSet(new FieldName('', 'parent field'), new Message('I always fail', []))
                ),
                new BeforeSet(new Fails()),
                1,
            ],
            'checks it can return noResult' => [
                Result::noResult(1),
                new BeforeSet(new Indifferent()),
                1,
            ],
            'checks it keeps track of previous results' => [
                Result::valid(1),
                new BeforeSet(new Passes(), new Indifferent(), new Indifferent()),
                1,

            ],
            'checks it can make changes to value' => [
                Result::noResult(2),
                new BeforeSet($filter1To2),
                1,
            ],
            'checks that changes made to value persist and chain runs in correct order' => [
                Result::noResult(3),
                new BeforeSet($filter1To2, $filter2To3),
                1,
            ],
            'checks that chain stops as soon as result is invalid' => [
                Result::invalid(1, new MessageSet(new FieldName('', 'parent field'), new Message('oh no!', []))),
                new BeforeSet($invalidate1, $filter1To2),
                1,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForFiltersOrValidators
     */
    public function processesCallsFilterOrValidateMethods(Result $expected, BeforeSet $sut, mixed $input): void
    {
        $actual = $sut->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $actual);
    }
}
