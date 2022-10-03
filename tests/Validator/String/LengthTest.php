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
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Result\MessageSet
 */
class LengthTest extends TestCase
{
    public function dataSetsWithIncorrectTypes(): array
    {
        return [
            [123, 'integer'],
            [1.23, 'double'],
            [[], 'array'],
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
        $length = new Length();
        $expected = Result::invalid($input, new MessageSet(
                null,
                new Message('Length Validator requires a string, %s given', [$expectedVars])
            )
        );

        $result = $length->validate($input);

        self::assertEquals($expected, $result);
    }

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
    public function stringLengthWithinMinAndMaxReturnsValid(mixed $input, int $min, ?int $max): void
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
    public function stringLengthOutsideMinOrMaxReturnsInvalid(
        mixed $input,
        int $min,
        ?int $max,
        Message $expectedMessage
    ): void {
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $length = new Length($min, $max);

        $result = $length->validate($input);

        self::assertEquals($expected, $result);
    }
}
