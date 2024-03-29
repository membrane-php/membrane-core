<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\Type;

use Membrane\Filter\Type\ToInt;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ToInt::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class ToIntTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'convert to an integer';
        $sut = new ToInt();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new ToInt();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsWithAcceptableInputs(): array
    {
        return [
            [1, 1],
            [1.23, 1],
            ['123', 123],
            [true, 1],
            [null, 0],
        ];
    }

    #[DataProvider('dataSetsWithAcceptableInputs')]
    #[Test]
    public function acceptableTypesReturnIntegerValues($input, $expectedValue): void
    {
        $toInt = new ToInt();
        $expected = Result::noResult($expectedValue);

        $result = $toInt->filter($input);

        self::assertSame($expected->value, $result->value);
        self::assertEquals($expected->result, $result->result);
    }

    public static function dataSetsWithUnacceptableInputs(): array
    {
        $message = 'ToInt filter only accepts null or scalar values, %s given';
        $class = new class () {
        };

        return [
            [
                'non-numeric string',
                new Message('ToInt filter only accepts numeric strings', []),
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
        $toInt = new ToInt();
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $toInt->filter($input);

        self::assertEquals($expected, $result);
    }
}
