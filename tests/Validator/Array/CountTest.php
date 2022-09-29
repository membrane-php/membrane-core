<?php

declare(strict_types=1);

namespace Validator\Array;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Array\Count;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Array\Count
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class CountTest extends TestCase
{
    /**
     * @test
     */
    public function noMinAndNoMaxReturnsValid(): void
    {
        $input = ['this', 'has', 'four', 'values'];
        $expected = Result::valid($input);
        $count = new Count();

        $result = $count->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsWithLessThanMinimum(): array
    {
        return [
            [[], 1],
            [['this', 'has', 'four', 'values'], 5],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithLessThanMinimum
     */
    public function arraysWithLessValuesThanMinimumReturnInvalid(array $input, int $min): void
    {
        $expectedMessage = new Message('Array is expected have a minimum of %d values', [$min]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $count = new Count($min);

        $result = $count->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsWithMoreThanMaximum(): array
    {
        return [
            [['two', 'values'], 1],
            [['this', 'has', 'four', 'more', 'than', 'the', 'maximum'], 3],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithMoreThanMaximum
     */
    public function arraysWithMoreValuesThanMaximumReturnInvalid(array $input, int $max): void
    {
        $expectedMessage = new Message('Array is expected have a minimum of %d values', [$max]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $count = new Count(0, $max);

        $result = $count->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsWithinRange(): array
    {
        return [
            [['two', 'values'], 1, 3],
            [['this', 'has', 'four', 'more', 'than', 'the', 'maximum'], 3, 10],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithinRange
     */
    public function arraysWithinRangeReturnValid(array $input, int $min, int $max): void
    {
        $expected = Result::valid($input);
        $count = new Count($min, $max);

        $result = $count->validate($input);

        self::assertEquals($expected, $result);
    }
}
