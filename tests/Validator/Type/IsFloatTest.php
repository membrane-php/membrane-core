<?php

declare(strict_types=1);

namespace Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsFloat;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Type\IsFloat
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class IsFloatTest extends TestCase
{
    /**
     * @test
     */
    public function floatReturnValid(): void
    {
        $input = 1.1;
        $isFloat = new IsFloat();
        $expected = Result::valid($input);

        $result = $isFloat->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsThatFail(): array
    {
        return [
            [true, 'boolean'],
            [1, 'integer'],
            ['1.1', 'string'],
            [[], 'array'],
            [null, 'NULL'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatFail
     */
    public function typesThatAreNotFloatReturnInvalid($input, $expectedVar): void
    {
        $isFloat = new IsFloat();
        $expectedMessage = new Message(
            'IsFloat expects float value, %s passed instead',
            [$expectedVar]
        );
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $isFloat->validate($input);

        self::assertEquals($expected, $result);
    }
}
