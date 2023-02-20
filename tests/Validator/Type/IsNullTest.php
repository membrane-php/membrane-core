<?php

declare(strict_types=1);

namespace Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsNull;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Type\IsNull
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class IsNullTest extends TestCase
{
    /** @test */
    public function toStringTest(): void
    {
        $expected = 'is null';
        $sut = new IsNull();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    /** @test */
    public function toPHPTest(): void
    {
        $sut = new IsNull();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    /**
     * @test
     */
    public function validForNullValue(): void
    {
        $sut = new IsNull();
        $expected = Result::valid(null);

        $actual = $sut->validate(null);

        self::assertEquals($expected, $actual);
    }

    public static function dataSetsThatFail(): array
    {
        return [
            [true, 'boolean'],
            [1, 'integer'],
            [1.1, 'double'],
            [[], 'array'],
            ['null', 'string'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatFail
     */
    public function invalidForNotNullTypes($input, $expectedVar): void
    {
        $sut = new IsNull();
        $expectedMessage = new Message(
            'IsNull validator expects null value, %s passed instead',
            [$expectedVar]
        );
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $actual = $sut->validate($input);

        self::assertEquals($expected, $actual);
    }
}
