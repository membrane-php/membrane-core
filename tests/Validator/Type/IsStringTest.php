<?php

declare(strict_types=1);

namespace Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsString;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Type\IsString
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class IsStringTest extends TestCase
{
    /**
     * @test
     */
    public function stringsReturnValid(): void
    {
        $input = 'this is a string';
        $isString = new IsString();
        $expected = Result::valid($input);

        $result = $isString->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsThatFail(): array
    {
        return [
            [true, 'boolean'],
            [1, 'integer'],
            [1.1, 'double'],
            [[], 'array'],
            [null, 'NULL'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatFail
     */
    public function typesThatAreNotStringReturnInvalid($input, $expectedVar): void
    {
        $isString = new IsString();
        $expectedMessage = new Message(
            'Value passed to IsString validator is not a string, %s passed instead',
            [$expectedVar]
        );
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $isString->validate($input);

        self::assertEquals($expected, $result);
    }
}
