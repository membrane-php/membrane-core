<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\Numeric;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Numeric\Maximum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Maximum::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class MaximumTest extends TestCase
{
    public static function dataSetsToConvertToString(): array
    {
        return [
            'inclusive max' => [
                5,
                false,
                'is less than or equal to 5',
            ],
            'exclusive max' => [
                7,
                true,
                'is less than 7',
            ],
        ];
    }

    #[DataProvider('dataSetsToConvertToString')]
    #[Test]
    public function toStringTest(int $max, bool $exclusive, string $expected): void
    {
        $sut = new Maximum($max, $exclusive);

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    public static function dataSetsToConvertToPHPString(): array
    {
        return [
            'inclusive maximum' => [new Maximum(5)],
            'exclusive maximum' => [new Maximum(5, true)],
        ];
    }

    #[DataProvider('dataSetsToConvertToPHPString')]
    #[Test]
    public function toPHPTest(Maximum $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsOfNonNumericValues(): array
    {
        return [
            [
                [1, 2, 3],
                ['array'],
            ],
            [
                true,
                ['boolean'],
            ],
            [
                'non-numeric string',
                ['string'],
            ],
            [
                null,
                ['NULL'],
            ],
        ];
    }

    #[DataProvider('dataSetsOfNonNumericValues')]
    #[Test]
    public function invalidForNonNumericValues(mixed $value, array $messageVars): void
    {
        $expected = Result::invalid(
            $value,
            new MessageSet(null, new Message('Maximum validator requires a number, %s given', $messageVars))
        );
        $sut = new Maximum(0);

        $actual = $sut->validate($value);

        self::assertEquals($expected, $actual);
    }

    public static function dataSetsToValidate(): array
    {
        return [
            'less than max (int, inclusive)' => [
                2,
                false,
                1,
                Result::valid(1),
            ],
            'less than max (int, exclusive)' => [
                2,
                true,
                1,
                Result::valid(1),
            ],
            'equal to max (int, inclusive)' => [
                5,
                false,
                5,
                Result::valid(5),
            ],
            'equal to max (int, exclusive)' => [
                5,
                true,
                5,
                Result::invalid(5, new MessageSet(null, new Message('Number has an exclusive maximum of %d', [5]))),
            ],
            'greater than max (int, inclusive)' => [
                5,
                false,
                10,
                Result::invalid(10, new MessageSet(null, new Message('Number has an inclusive maximum of %d', [5]))),
            ],
            'greater than max (int, exclusive)' => [
                5,
                true,
                10,
                Result::invalid(10, new MessageSet(null, new Message('Number has an exclusive maximum of %d', [5]))),
            ],
            'less than max (float, inclusive)' => [
                2.5,
                false,
                2.4,
                Result::valid(2.4),
            ],
            'less than max (float, exclusive)' => [
                2.5,
                true,
                2.4,
                Result::valid(2.4),
            ],
            'equal to max (float, inclusive)' => [
                5,
                false,
                5.0,
                Result::valid(5.0),
            ],
            'equal to max (float, exclusive)' => [
                5,
                true,
                5.0,
                Result::invalid(5.0, new MessageSet(null, new Message('Number has an exclusive maximum of %d', [5.0]))),
            ],
            'greater than max (float, inclusive)' => [
                5.5,
                false,
                10.1,
                Result::invalid(
                    10.1,
                    new MessageSet(null, new Message('Number has an inclusive maximum of %d', [5.5]))
                ),
            ],
            'greater than max (float, exclusive)' => [
                5.5,
                true,
                10.1,
                Result::invalid(
                    10.1,
                    new MessageSet(null, new Message('Number has an exclusive maximum of %d', [5.5]))
                ),
            ],
        ];
    }

    #[DataProvider('dataSetsToValidate')]
    #[Test]
    public function validateTest(int | float $max, bool $exclusive, int | float $value, Result $expected): void
    {
        $sut = new Maximum($max, $exclusive);

        $actual = $sut->validate($value);

        self::assertEquals($expected, $actual);
    }
}
