<?php

declare(strict_types=1);

namespace Validator\Numeric;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Numeric\Minimum;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Numeric\Minimum
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class MinimumTest extends TestCase
{
    public function dataSetsOfNonNumericValues(): array
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

    /**
     * @test
     * @dataProvider dataSetsOfNonNumericValues
     */
    public function invalidForNonNumericValues(mixed $value, array $messageVars): void
    {
        $expected = Result::invalid(
            $value,
            new MessageSet(null, new Message('Minimum validator requires a number, %s given', $messageVars))
        );
        $sut = new Minimum(0);

        $actual = $sut->validate($value);

        self::assertEquals($expected, $actual);
    }

    public function dataSetsToValidate(): array
    {
        return [
            'greater than min (int, inclusive)' => [
                1,
                false,
                2,
                Result::valid(2),
            ],
            'greater than min (int, exclusive)' => [
                1,
                true,
                2,
                Result::valid(2),
            ],
            'equal to min (int, inclusive)' => [
                5,
                false,
                5,
                Result::valid(5),
            ],
            'equal to min (int, exclusive)' => [
                5,
                true,
                5,
                Result::invalid(5, new MessageSet(null, new Message('Number has an exclusive minimum of %d', [5]))),
            ],
            'less than min (int, inclusive)' => [
                10,
                false,
                5,
                Result::invalid(5, new MessageSet(null, new Message('Number has an inclusive minimum of %d', [10]))),
            ],
            'less than min (int, exclusive)' => [
                10,
                true,
                5,
                Result::invalid(5, new MessageSet(null, new Message('Number has an exclusive minimum of %d', [10]))),
            ],
            'greater than min (float, inclusive)' => [
                2.4,
                false,
                2.5,
                Result::valid(2.5),
            ],
            'greater than min (float, exclusive)' => [
                2.4,
                true,
                2.5,
                Result::valid(2.5),
            ],
            'equal to min (float, inclusive)' => [
                5,
                false,
                5.0,
                Result::valid(5.0),
            ],
            'equal to min (float, exclusive)' => [
                5,
                true,
                5.0,
                Result::invalid(5.0, new MessageSet(null, new Message('Number has an exclusive minimum of %d', [5.0]))),
            ],
            'less than min (float, inclusive)' => [
                10.1,
                false,
                5.5,
                Result::invalid(
                    5.5,
                    new MessageSet(null, new Message('Number has an inclusive minimum of %d', [10.1]))
                ),
            ],
            'less than min (float, exclusive)' => [
                10.1,
                true,
                5.5,
                Result::invalid(
                    5.5,
                    new MessageSet(null, new Message('Number has an exclusive minimum of %d', [10.1]))
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToValidate
     */
    public function validateTest(int|float $min, bool $exclusive, int|float $value, Result $expected): void
    {
        $sut = new Minimum($min, $exclusive);

        $actual = $sut->validate($value);

        self::assertEquals($expected, $actual);
    }
}
