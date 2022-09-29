<?php

declare(strict_types=1);

namespace Filter\Type;

use Membrane\Filter\Type\ToBool;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\Type\ToBool
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class ToBoolTest extends TestCase
{
    public function dataSetsWithAcceptableInputs(): array
    {
        return [
            [1, true],
            [1.0, true],
            [true, true],
            ['true', true],
            ['on', true],
            ['yes', true],
            [0, false],
            [0.0, false],
            [false, false],
            ['false', false],
            ['off', false],
            ['no', false],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithAcceptableInputs
     */
    public function acceptableTypesReturnBooleanValues($input, $expectedValue): void
    {
        $toBool = new ToBool();
        $expected = Result::noResult($expectedValue);

        $result = $toBool->filter($input);

        self::assertSame($expected->value, $result->value);
        self::assertEquals($expected->result, $result->result);
    }

    public function dataSetsWithUnacceptableInputs(): array
    {
        $unacceptableMessage = 'ToBool filter only accepts scalar values, %s given';
        $failureMessage = 'ToBool filter failed to convert value to boolean';
        $class = new class () {
        };

        return [
            [
                'string with true inside but it is not the only word',
                new Message($failureMessage, []),
            ],
            [
                2,
                new Message($failureMessage, []),
            ],
            [
                0.1,
                new Message($failureMessage, []),
            ],
            [
                ['an', 'array', 'with', 'true', 'inside'],
                new Message($unacceptableMessage, ['array']),
            ],
            [
                ['a' => 'list'],
                new Message($unacceptableMessage, ['array']),
            ],
            [
                $class,
                new Message($unacceptableMessage, ['object']),
            ],
            [
                null,
                new Message($unacceptableMessage, ['NULL']),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithUnacceptableInputs
     */
    public function unacceptableTypesReturnInvalid($input, $expectedMessage): void
    {
        $toBool = new ToBool();
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $toBool->filter($input);

        self::assertEquals($expected, $result);
    }
}
