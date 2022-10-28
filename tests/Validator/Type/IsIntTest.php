<?php

declare(strict_types=1);

namespace Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsInt;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Type\IsInt
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class IsIntTest extends TestCase
{
    /**
     * @test
     */
    public function integerReturnValid(): void
    {
        $input = 10;
        $isInt = new IsInt();
        $expected = Result::valid($input);

        $result = $isInt->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsThatFail(): array
    {
        return [
            ['1', 'string'],
            [true, 'boolean'],
            [1.1, 'double'],
            [[], 'array'],
            [null, 'NULL'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatFail
     */
    public function typesThatAreNotIntegerReturnInvalid($input, $expectedVar): void
    {
        $isInt = new IsInt();
        $expectedMessage = new Message(
            'IsInt validator expects integer value, %s passed instead',
            [$expectedVar]
        );
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $isInt->validate($input);

        self::assertEquals($expected, $result);
    }
}
