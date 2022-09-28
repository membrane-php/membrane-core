<?php

namespace Validator\Numeric;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Numeric\Range;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Numeric\Range
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class RangeTest extends TestCase
{
    /**
     * @test
     */
    public function nullMinAndNullMaxReturnsValid(): void
    {
        $input = 1;
        $expected = Result::valid($input);
        $range = new Range();

        $result = $range->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsForInputsBelowMinimum(): array
    {
        return [
            [0, 5],
            [-5, 0],
            [2.71, 3.41],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForInputsBelowMinimum
     */
    public function numbersBelowMinimumReturnInvalid(int|float $input, int|float $min): void
    {
        $expectedMessage = new Message('Number is expected to be a minimum of %d', [$min]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $range = new Range($min);

        $result = $range->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsForInputsAboveMaximum(): array
    {
        return [
            [10, 5],
            [-5, -10],
            [3.41, 2.71],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForInputsAboveMaximum
     */
    public function numbersAboveMaximumReturnInvalid(int|float $input, int|float $max): void
    {
        $expectedMessage = new Message('Number is expected to be a maximum of %d', [$max]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $range = new Range(null, $max);

        $result = $range->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsForInputsWithinRange(): array
    {
        return [
            [5, 0, 5],
            [-5, -10, 0],
            [3.41, 2.71, 9.81],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForInputsWithinRange
     */
    public function numbersWithinRangeReturnValid(int|float $input, int|float $min, int|float $max): void
    {
        $expected = Result::valid($input);
        $range = new Range($min, $max);

        $result = $range->validate($input);

        self::assertEquals($expected, $result);
    }
}
