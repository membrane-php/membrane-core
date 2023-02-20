<?php

declare(strict_types=1);

namespace Filter\Type;

use Membrane\Filter\Type\ToList;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ToList::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class ToListTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'convert to a list';
        $sut = new ToList();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new ToList();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsWithAcceptableInputs(): array
    {
        return [
            [[], []],
            [['this', 'is', 'a', 'list'], ['this', 'is', 'a', 'list']],
            [['even' => 'this', 'array' => 'is', 'becomes' => 'a', 'simple' => 'list'], ['this', 'is', 'a', 'list']],
        ];
    }

    #[DataProvider('dataSetsWithAcceptableInputs')]
    #[Test]
    public function acceptableTypesReturnListValues($input, $expectedValue): void
    {
        $toList = new ToList();
        $expected = Result::noResult($expectedValue);

        $result = $toList->filter($input);

        self::assertSame($expected->value, $result->value);
        self::assertEquals($expected->result, $result->result);
    }

    public static function dataSetsWithUnacceptableInputs(): array
    {
        $message = 'ToList filter only accepts arrays, %s given';
        $class = new class () {
        };

        return [
            [
                'string',
                new Message($message, ['string']),
            ],
            [
                123,
                new Message($message, ['integer']),
            ],
            [
                1.23,
                new Message($message, ['double']),
            ],
            [
                true,
                new Message($message, ['boolean']),
            ],
            [
                null,
                new Message($message, ['NULL']),
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
        $toList = new ToList();
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $toList->filter($input);

        self::assertEquals($expected, $result);
    }
}
