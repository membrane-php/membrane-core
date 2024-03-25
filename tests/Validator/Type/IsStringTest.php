<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IsString::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class IsStringTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'is a string';
        $sut = new IsString();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new IsString();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    #[Test]
    public function stringsReturnValid(): void
    {
        $input = 'this is a string';
        $isString = new IsString();
        $expected = Result::valid($input);

        $result = $isString->validate($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsThatFail(): array
    {
        return [
            [true, 'boolean'],
            [1, 'integer'],
            [1.1, 'double'],
            [[], 'array'],
            [null, 'NULL'],
        ];
    }

    #[DataProvider('dataSetsThatFail')]
    #[Test]
    public function typesThatAreNotStringReturnInvalid($input, $expectedVar): void
    {
        $isString = new IsString();
        $expectedMessage = new Message(
            'IsString validator expects string value, %s passed instead',
            [$expectedVar]
        );
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $isString->validate($input);

        self::assertEquals($expected, $result);
    }
}
