<?php

declare(strict_types=1);

namespace Validator\Collection;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Collection\Count;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Collection\Count
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class CountTest extends TestCase
{
    public function dataSetsWithIncorrectTypes(): array
    {
        return [
            [123, 'integer'],
            [1.23, 'double'],
            ['string', 'string'],
            [true, 'boolean'],
            [null, 'NULL'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithIncorrectTypes
     */
    public function incorrectTypesReturnInvalidResults($input, $expectedVars): void
    {
        $count = new Count();
        $expected = Result::invalid(
            $input,
            new MessageSet(
                null,
                new Message('Count Validator requires an array, %s given', [$expectedVars])
            )
        );

        $result = $count->validate($input);

        self::assertEquals($expected, $result);
    }

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
        $expectedMessage = new Message('Array is expected have a maximum of %d values', [$max]);
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
