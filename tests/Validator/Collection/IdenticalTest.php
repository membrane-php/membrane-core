<?php
declare(strict_types=1);

namespace Validator\Collection;

use Membrane\Result\Result;
use Membrane\Validator\Collection\Identical;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Collection\Identical
 */
class IdenticalTest extends TestCase
{
    /**
     * @return array
     */
    public function dataSetsThatPass(): array
    {
        return [
            [[], Result::VALID],
            [[[],[]], Result::VALID],
            [[[1,5],[1,5]], Result::VALID],
            [[1], Result::VALID],
            [[1, 1], Result::VALID],
            [[null, null], Result::VALID],
            [[25, 5*5, 100/4, 20+5], Result::VALID],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatPass
     */
    public function ReturnsValidIfEveryInputIsIdentical(mixed $input, int $expected): void
    {
        $identical = new Identical;
        $result = $identical->validate($input);
        self::assertEquals($expected, $result->result);
    }

    /**
     * @return array
     */
    public function dataSetsThatFail(): array
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
            [[1,2,3,4,5], Result::INVALID],
            [[[1,5],[5,1]], Result::INVALID],
        ];
    }
    /**
     * @test
     * @dataProvider dataSetsThatFail
     */
    public function ReturnsInvalidIfAnyInputIsDifferent(mixed $input, int $expected): void
    {
        $identical = new Identical;

        $result = $identical->validate($input);

        self::assertEquals('Do not match', $result->messageSets[0]?->messages[0]?->message);
        self::assertEquals($expected, $result->result);
    }
}