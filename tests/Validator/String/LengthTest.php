<?php
declare(strict_types=1);

namespace Validator\String;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\String\Length;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\String\Length
 * @uses \Membrane\Result\Result
 * @uses \Membrane\Result\Message
 * @uses \Membrane\Result\MessageSet
 */
class LengthTest extends TestCase
{
    public function dataSetsThatPass(): array
    {
        return [
            ['', 0, 0],
            ['', 0, null],
            ['', 0, 5],
            ['short', 0, 5],
            ['longer string', 5, 100],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatPass
     */
    public function StringLengthWithinMinAndMaxReturnsValid(mixed $input, int $min, ?int $max): void
    {
        $expected = Result::valid($input);
        $length = new Length($min, $max);

        $result = $length->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsThatFail(): array
    {
        return [
            ['', 1, 5, new Message('String is expected to be a minimum of %d characters', [1])],
            ['short', 6, 10, new Message('String is expected to be a minimum of %d characters', [6])],
            ['longer string.', 6, 10, new Message('String is expected to be a maximum of %d characters', [10])],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatFail
     */
    public function StringLengthOutsideMinOrMaxReturnsInvalid(mixed $input, int $min, ?int $max, Message $expectedMessage): void
    {
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $length = new Length($min, $max);

        $result = $length->validate($input);

        self::assertEquals($expected, $result);
    }
}