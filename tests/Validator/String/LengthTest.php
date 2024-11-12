<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\String;

use Generator;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\String\Length;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Length::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class LengthTest extends TestCase
{
    #[Test]
    #[DataProvider('provideToStringCases')]
    public function toStringTest(int $min, ?int $max, string $expected): void
    {
        $sut = new Length($min, $max);

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    #[DataProvider('provideToPHPCases')]
    public function toPHPTest(Length $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    #[Test]
    #[DataProvider('provideNonStringTypes')]
    public function itInvalidatesNonStringTypes(mixed $input): void
    {
        $length = new Length();
        $expected = Result::invalid(
            $input,
            new MessageSet(
                null,
                new Message(
                    'Length Validator requires a string, %s given',
                    [gettype($input)]
                )
            )
        );

        $result = $length->validate($input);

        self::assertEquals($expected, $result);
    }

    #[Test]
    #[DataProvider('provideStringsToValidate')]
    public function itValidatesStringLengths(
        Result $expected,
        int $min,
        ?int $max,
        string $input
    ): void {
        $sut = new Length($min, $max);

        $actual = $sut->validate($input);

        self::assertEquals($expected, $actual);
    }

    /**
     * @return array<array{
     *     0: int,
     *     1: ?int,
     *     2: string,
     * }>
     */
    public static function provideToStringCases(): array
    {
        return [
            'no conditions' => [
                0,
                null,
                'will return valid',
            ],
            'non-zero min' => [
                1,
                null,
                'is 1 characters or more',
            ],
            'non-null max' => [
                0,
                5,
                'is 5 characters or less',
            ],
            'non-zero min and non-null max' => [
                2,
                4,
                'is 2 characters or more and is 4 characters or less',
            ],
        ];
    }


    public static function provideToPHPCases(): array
    {
        return [
            'default arguments' => [new Length()],
            'assigned arguments' => [new Length(1, 5)],
        ];
    }

    public static function provideNonStringTypes(): array
    {
        return [
            [123],
            [1.23],
            [[]],
            [true],
            [null],
        ];
    }

    /**
     * @return Generator<array{
     *     0: Result,
     *     1: int,
     *     2: ?int,
     *     3: string,
     * }>
     */
    public static function provideStringsToValidate(): Generator
    {
        $invalidCase = fn($input, $min, $max, $message) => [
            Result::invalid($input, new MessageSet(null, $message)),
            $min,
            $max,
            $input,
        ];

        $validCase = fn($input, $min, $max) => [
            Result::valid($input),
            $min,
            $max,
            $input,
        ];

        yield 'empty string below min' => $invalidCase(
            '',
            1,
            null,
            new Message('String is expected to be a minimum of %d characters', [1])
        );

        yield 'empty string within range (inclusive min)' => $validCase(
            '',
            0,
            null
        );

        yield 'empty string within range (inclusive min and max)' => $validCase(
            '',
            0,
            0,
        );

        yield '"string" with min of zero and no max' => $validCase(
            'string',
            0,
            null,
        );

        yield '"string" within range' => $validCase(
            'string',
            5,
            7,
        );

        yield '"string" within range (inclusive min)' => $validCase(
            'string',
            6,
            7,
        );

        yield '"string" within range (inclusive max)' => $validCase(
            'string',
            5,
            6,
        );

        yield '"string" within range (inclusive min and max)' => $validCase(
            'string',
            6,
            6,
        );

        yield '"short" below min' => $invalidCase(
            'short',
            6,
            null,
            new Message('String is expected to be a minimum of %d characters', [6])
        );

        yield '"long" above max' => $invalidCase(
            'long',
            0,
            3,
            new Message('String is expected to be a maximum of %d characters', [3])
        );

        yield '"äöü" within range' => $validCase(
            'äöü',
            2,
            4,
        );

        yield '"äöü" within range (inclusive min)' => $validCase(
            'äöü',
            3,
            4,
        );

        yield '"äöü" within range (inclusive max)' => $validCase(
            'äöü',
            2,
            5,
        );

        yield '"äöü" within range (inclusive min and max)' => $validCase(
            'äöü',
            3,
            3,
        );
    }
}
