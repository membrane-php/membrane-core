<?php

declare(strict_types=1);

namespace Filter\Type;

use Membrane\Filter\Type\ToBool;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ToBool::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class ToBoolTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'convert to a boolean';
        $sut = new ToBool();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new ToBool();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsWithAcceptableInputs(): array
    {
        return [
            [1, true],
            [1.0, true],
            [true, true],
            ['true', true],
            ['on', true],
            ['yes', true],
            [0, false],
            [0.0, false],
            [false, false],
            ['false', false],
            ['off', false],
            ['no', false],
        ];
    }

    #[DataProvider('dataSetsWithAcceptableInputs')]
    #[Test]
    public function acceptableTypesReturnBooleanValues($input, $expectedValue): void
    {
        $toBool = new ToBool();
        $expected = Result::noResult($expectedValue);

        $result = $toBool->filter($input);

        self::assertSame($expected->value, $result->value);
        self::assertEquals($expected->result, $result->result);
    }

    public static function dataSetsWithUnacceptableInputs(): array
    {
        $unacceptableMessage = 'ToBool filter only accepts scalar values, %s given';
        $failureMessage = 'ToBool filter failed to convert value to boolean';
        $class = new class () {
        };

        return [
            [
                'string with true inside but it is not the only word',
                new Message($failureMessage, []),
            ],
            [
                2,
                new Message($failureMessage, []),
            ],
            [
                0.1,
                new Message($failureMessage, []),
            ],
            [
                ['an', 'array', 'with', 'true', 'inside'],
                new Message($unacceptableMessage, ['array']),
            ],
            [
                ['a' => 'list'],
                new Message($unacceptableMessage, ['array']),
            ],
            [
                $class,
                new Message($unacceptableMessage, ['object']),
            ],
            [
                null,
                new Message($unacceptableMessage, ['NULL']),
            ],
        ];
    }

    #[DataProvider('dataSetsWithUnacceptableInputs')]
    #[Test]
    public function unacceptableTypesReturnInvalid($input, $expectedMessage): void
    {
        $toBool = new ToBool();
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $toBool->filter($input);

        self::assertEquals($expected, $result);
    }
}
