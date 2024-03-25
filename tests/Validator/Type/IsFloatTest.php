<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsFloat;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IsFloat::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class IsFloatTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'is a float';
        $sut = new IsFloat();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new IsFloat();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    #[Test]
    public function floatReturnValid(): void
    {
        $input = 1.1;
        $isFloat = new IsFloat();
        $expected = Result::valid($input);

        $result = $isFloat->validate($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsThatFail(): array
    {
        return [
            [true, 'boolean'],
            [1, 'integer'],
            ['1.1', 'string'],
            [[], 'array'],
            [null, 'NULL'],
        ];
    }

    #[DataProvider('dataSetsThatFail')]
    #[Test]
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
