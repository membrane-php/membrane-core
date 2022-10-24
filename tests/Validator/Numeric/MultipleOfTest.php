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
        $notNumMessage = 'MultipleOf validator requires a number, %s given';


        $notMultipleMessage = 'Number is expected to be a multiple of %d';
        return [
            'array values are not numeric' => [
                [1, 2, 3],
                5,
                Result::invalid([1, 2, 3], new MessageSet(null, new Message($notNumMessage, ['array']))),
            ],
            'boolean values are not numeric' => [
                true,
                5,
                Result::invalid(true, new MessageSet(null, new Message($notNumMessage, ['boolean']))),
            ],
            'non-numeric strings are not numeric' => [
                'non-numeric string',
                5,
                Result::invalid('non-numeric string', new MessageSet(null, new Message($notNumMessage, ['string']))),
            ],
            'null values are not numeric' => [
                null,
                5,
                Result::invalid(null, new MessageSet(null, new Message($notNumMessage, ['NULL']))),
            ],
            'not a multiple (integer)' => [
                10,
                7,
                Result::invalid(10, new MessageSet(null, new Message($notMultipleMessage, [7]))),
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
                Result::invalid(10, new MessageSet(null, new Message($notMultipleMessage, [7.5]))),
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
            'not a multiple (string)' => [
                '10',
                7,
                Result::invalid('10', new MessageSet(null, new Message($notMultipleMessage, [7]))),
            ],
            'positive multiple (string)' => [
                '10',
                5,
                Result::valid('10'),
            ],
            'negative multiple (string)' => [
                '-10',
                5,
                Result::valid('-10'),
            ],
            'factor and multiple the wrong way round' => [
                10,
                100,
                Result::invalid(10, new MessageSet(null, new Message($notMultipleMessage, [100]))),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToValidate
     */
    public function validateTest(mixed $value, float|int $multiple, $expected): void
    {
        $multipleOf = new MultipleOf($multiple);

        $actual = $multipleOf->validate($value);

        self::assertEquals($expected, $actual);
    }


}
