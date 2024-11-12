<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\Type;

use Membrane\Filter\Type\ToNumber;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ToNumber::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
class ToNumberTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'convert to a number';
        $sut = new ToNumber();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new ToNumber();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsToFilter(): array
    {
        $class = new class {
        };

        return [
            'filters integer' => [
                1,
                Result::valid(1),
            ],
            'filters float' => [
                2.3,
                Result::valid(2.3),
            ],
            'filters integer string' => [
                '4',
                Result::valid(4),
            ],
            'filters float string' => [
                '5.6',
                Result::valid(5.6),
            ],
            'invalidates non-numeric string' => [
                'six',
                Result::invalid(
                    'six',
                    new MessageSet(null, new Message('ToNumber filter expects numeric values, %s passed', ['string']))
                ),
            ],
            'invalidates bool' => [
                true,
                Result::invalid(
                    true,
                    new MessageSet(null, new Message('ToNumber filter expects numeric values, %s passed', ['boolean']))
                ),
            ],
            'invalidates null' => [
                null,
                Result::invalid(
                    null,
                    new MessageSet(null, new Message('ToNumber filter expects numeric values, %s passed', ['NULL']))
                ),
            ],
            'invalidates array' => [
                [1, 2, 3],
                Result::invalid(
                    [1, 2, 3],
                    new MessageSet(null, new Message('ToNumber filter expects numeric values, %s passed', ['array']))
                ),
            ],
            'invalidates object' => [
                $class,
                Result::invalid(
                    $class,
                    new MessageSet(null, new Message('ToNumber filter expects numeric values, %s passed', ['object']))
                ),
            ],
        ];
    }

    #[DataProvider('dataSetsToFilter')]
    #[Test]
    public function validateTest(mixed $value, Result $expected): void
    {
        $sut = new ToNumber();

        $actual = $sut->filter($value);

        self::assertSame($expected->value, $actual->value);
        self::assertEquals($expected, $actual);
    }
}
