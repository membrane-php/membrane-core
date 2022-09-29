<?php

declare(strict_types=1);

namespace Filter\Type;

use Membrane\Filter\Type\ToFloat;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\Type\ToFloat
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class ToFloatTest extends TestCase
{
    public function dataSetsWithAcceptableInputs(): array
    {
        return [
            [1, 1.0],
            [1.23, 1.23],
            ['123', 123.0],
            [true, 1.0],
            [null, 0.0],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithAcceptableInputs
     */
    public function acceptableTypesReturnFloatValues($input, $expectedValue): void
    {
        $toFloat = new ToFloat();
        $expected = Result::noResult($expectedValue);

        $result = $toFloat->filter($input);

        self::assertSame($expected->value, $result->value);
        self::assertEquals($expected->result, $result->result);
    }

    public function dataSetsWithUnacceptableInputs(): array
    {
        $message = 'ToFloat filter only accepts null or scalar values, %s given';
        $class = new class () {
        };

        return [
            [
                'non-numeric string',
                new Message('ToFloat filter only accepts numeric strings', []),
            ],
            [
                ['an', 'array'],
                new Message($message, ['array']),
            ],
            [
                ['a' => 'list'],
                new Message($message, ['array']),
            ],
            [
                $class,
                new Message($message, ['object']),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithUnacceptableInputs
     */
    public function unacceptableTypesReturnInvalid($input, $expectedMessage): void
    {
        $toFloat = new ToFloat();
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $toFloat->filter($input);

        self::assertEquals($expected, $result);
    }
}
