<?php

declare(strict_types=1);

namespace Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsBool;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IsBool::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class IsBoolTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'is a boolean';
        $sut = new IsBool();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new IsBool();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    #[Test]
    public function booleanReturnValid(): void
    {
        $input = false;
        $isBool = new IsBool();
        $expected = Result::valid($input);

        $result = $isBool->validate($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsThatFail(): array
    {
        return [
            ['true', 'string'],
            [1, 'integer'],
            [1.1, 'double'],
            [[], 'array'],
            [null, 'NULL'],
        ];
    }

    #[DataProvider('dataSetsThatFail')]
    #[Test]
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
