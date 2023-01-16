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
    public function dataSetsToConvertToString(): array
    {
        return [
            'no conditions' => [
                0,
                null,
                'will return valid',
            ],
            'non-zero minimum provided' => [
                1,
                null,
                'is 1 characters or more',
            ],
            'maximum provided' => [
                0,
                5,
                'is 5 characters or less',
            ],
            'minimum and maximum provided' => [
                2,
                4,
                'is 2 characters or more and is 4 characters or less',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToConvertToString
     */
    public function toStringTest(int $min, ?int $max, string $expected): void
    {
        $sut = new Length($min, $max);

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

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
        $expected = Result::invalid(
            $input,
            new MessageSet(
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
