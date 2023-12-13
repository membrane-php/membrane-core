<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\Type;

use Membrane\Filter\Type\ToFloat;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ToFloat::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class ToFloatTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'convert to a float';
        $sut = new ToFloat();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new ToFloat();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsWithAcceptableInputs(): array
    {
        return [
            [1, 1.0],
            [1.23, 1.23],
            ['123', 123.0],
            [true, 1.0],
            [null, 0.0],
        ];
    }

    #[DataProvider('dataSetsWithAcceptableInputs')]
    #[Test]
    public function acceptableTypesReturnFloatValues($input, $expectedValue): void
    {
        $toFloat = new ToFloat();
        $expected = Result::noResult($expectedValue);

        $result = $toFloat->filter($input);

        self::assertSame($expected->value, $result->value);
        self::assertEquals($expected->result, $result->result);
    }

    public static function dataSetsWithUnacceptableInputs(): array
    {
        $message = 'ToFloat filter only accepts null or scalar values, %s given';
        $class = new class () {
        };

        return [
            [
                'non-numeric string',
                new Message('ToFloat filter only accepts numeric strings', []),
            ],
            [
                ['an', 'array'],
                new Message($message, ['array']),
            ],
            [
                ['a' => 'list'],
                new Message($message, ['array']),
            ],
            [
                $class,
                new Message($message, ['object']),
            ],
        ];
    }

    #[DataProvider('dataSetsWithUnacceptableInputs')]
    #[Test]
    public function unacceptableTypesReturnInvalid($input, $expectedMessage): void
    {
        $toFloat = new ToFloat();
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $toFloat->filter($input);

        self::assertEquals($expected, $result);
    }
}
