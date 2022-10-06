<?php

declare(strict_types=1);

namespace Validator\Collection;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Collection\Identical;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Collection\Identical
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class IdenticalTest extends TestCase
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
        $identical = new Identical();
        $expected = Result::invalid($input, new MessageSet(
                null,
                new Message('Identical Validator requires an array, %s given', [$expectedVars])
            )
        );

        $result = $identical->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsForValidResults(): array
    {
        return [
            [[]],
            [[[], []]],
            [[[1, 5], [1, 5]]],
            [[1]],
            [[1, 1]],
            [[null, null]],
            [[25, 5 * 5, 100 / 4, 20 + 5]],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForValidResults
     */
    public function returnsValidIfEveryInputIsIdentical(mixed $input): void
    {
        $expected = Result::valid($input);
        $identical = new Identical();

        $result = $identical->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsForInvalidResults(): array
    {
        return [
            [[null, 1], Result::INVALID],
            [[1, 1.0], Result::INVALID],
            [[1, '1'], Result::INVALID],
            [[true, false], Result::INVALID],
            [[true, 'true'], Result::INVALID],
            [[false, ''], Result::INVALID],
            [[null, ''], Result::INVALID],
            [[1, 1, 2], Result::INVALID],
            [[1, 2, 3, 4, 5], Result::INVALID],
            [[[1, 5], [5, 1]], Result::INVALID],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForInvalidResults
     */
    public function returnsInvalidIfAnyInputIsDifferent(mixed $input): void
    {
        $expected = Result::invalid($input, new MessageSet(null, new Message('Do not match', [])));
        $identical = new Identical();

        $result = $identical->validate($input);

        self::assertEquals($expected, $result);
    }
}
