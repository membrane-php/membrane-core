<?php

declare(strict_types=1);

namespace Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsBool;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Type\IsBool
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class IsBoolTest extends TestCase
{
    /**
     * @test
     */
    public function booleanReturnValid(): void
    {
        $input = false;
        $isBool = new IsBool();
        $expected = Result::valid($input);

        $result = $isBool->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsThatFail(): array
    {
        return [
            ['true', 'string'],
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
    public function typesThatAreNotBooleanReturnInvalid($input, $expectedVar): void
    {
        $isBool = new IsBool();
        $expectedMessage = new Message(
            'IsBool validator expects boolean value, %s passed instead',
            [$expectedVar]
        );
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $isBool->validate($input);

        self::assertEquals($expected, $result);
    }
}
