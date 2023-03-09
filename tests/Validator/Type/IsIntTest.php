<?php

declare(strict_types=1);

namespace Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsInt;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IsInt::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class IsIntTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'is an integer';
        $sut = new IsInt();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new IsInt();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    #[Test]
    public function integerReturnValid(): void
    {
        $input = 10;
        $isInt = new IsInt();
        $expected = Result::valid($input);

        $result = $isInt->validate($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsThatFail(): array
    {
        return [
            ['1', 'string'],
            [true, 'boolean'],
            [1.1, 'double'],
            [[], 'array'],
            [null, 'NULL'],
        ];
    }

    #[DataProvider('dataSetsThatFail')]
    #[Test]
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
