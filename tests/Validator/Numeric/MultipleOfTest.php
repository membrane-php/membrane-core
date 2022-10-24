<?php

declare(strict_types=1);

namespace Validator\Numeric;

use Exception;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Numeric\MultipleOf;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Numeric\MultipleOf
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class MultipleOfTest extends TestCase
{
    public function dataSetsThatThrowExceptions(): array
    {
        return [
            [0],
            [0.0],
            [-5],
            [-5.5],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatThrowExceptions
     */
    public function throwsExceptionForZeroOrNegatives(int|float $multiple): void
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage('MultipleOf validator does not support numbers of zero or less');

        new MultipleOf($multiple);
    }

    public function dataSetsToValidate(): array
    {
        return [
            'not a multiple (integer)' => [
                10,
                7,
                Result::invalid(
                    10,
                    new MessageSet(null, new Message('Number is expected to be a multiple of %d', [7]))
                ),
            ],
            'positive multiple (integer)' => [
                10,
                5,
                Result::valid(10),
            ],
            'negative multiple (integer)' => [
                -10,
                5,
                Result::valid(-10),
            ],
            'not a multiple (float)' => [
                10,
                7.5,
                Result::invalid(
                    10,
                    new MessageSet(null, new Message('Number is expected to be a multiple of %d', [7.5]))
                ),
            ],
            'positive multiple (float)' => [
                1,
                0.5,
                Result::valid(1),
            ],
            'negative multiple (float)' => [
                -1,
                0.5,
                Result::valid(-1),
            ],
            'factor and multiple the wrong way round' => [
                10,
                100,
                Result::invalid(
                    10,
                    new MessageSet(null, new Message('Number is expected to be a multiple of %d', [100]))
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToValidate
     */
    public function validateTest(float|int $value, float|int $multiple, $expected): void
    {
        $multipleOf = new MultipleOf($multiple);

        $actual = $multipleOf->validate($value);

        self::assertEquals($expected, $actual);
    }


}
