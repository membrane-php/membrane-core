<?php

declare(strict_types=1);

namespace Filter\Shape;

use Membrane\Filter\Shape\Pluck;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Pluck::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class PluckTest extends TestCase
{
    public static function dataSetsToConvertToString(): array
    {
        return [
            'no fields' => [
                [],
                '',
            ],
            'single field' => [
                ['a'],
                'collect "a" from "existing field set" and append them to self',
            ],
            'multiple fields' => [
                ['a', 'b', 'c'],
                'collect "a", "b", "c" from "existing field set" and append them to self',
            ],
        ];
    }

    #[DataProvider('dataSetsToConvertToString')]
    #[Test]
    public function toStringTest(array $fields, string $expected): void
    {
        $sut = new Pluck('existing field set', ...$fields);

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    public static function dataSetsToConvertToPHPString(): array
    {
        return [
            'no fields' => [new Pluck('field set')],
            'one field' => [new Pluck('field set', 'a')],
            'multiple fields' => [new Pluck('field set', 'a', 'b', 'c')],
        ];
    }

    #[DataProvider('dataSetsToConvertToPHPString')]
    #[Test]
    public function toPHPTest(Pluck $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsWithIncorrectInputs(): array
    {
        $notArrayMessage = 'Pluck filter requires arrays, %s given';
        $listMessage = 'Pluck filter requires arrays with key-value pairs';
        return [
            [123, new Message($notArrayMessage, ['integer'])],
            [1.23, new Message($notArrayMessage, ['double'])],
            ['this is a string', new Message($notArrayMessage, ['string'])],
            [true, new Message($notArrayMessage, ['boolean'])],
            [['this', 'is', 'a', 'list'], new Message($listMessage, [])],
        ];
    }

    #[DataProvider('dataSetsWithIncorrectInputs')]
    #[Test]
    public function incorrectInputsReturnInvalid(mixed $input, Message $expectedMessage): void
    {
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $pluck = new Pluck('from', 'a', 'b');

        $result = $pluck->filter($input);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function nonExistentFieldSetIsIgnored(): void
    {
        $input = ['not-from' => ['a' => 1, 'b' => 2, 'c' => 3], 'd' => 4];
        $expected = Result::noResult($input);
        $pluck = new Pluck('from', 'a', 'b', 'c');

        $result = $pluck->filter($input);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function nonExistentFieldNamesAreIgnored(): void
    {
        $input = ['from' => ['a' => 1, 'b' => 2, 'c' => 3], 'd' => 4];
        $expected = Result::noResult($input);
        $pluck = new Pluck('from', 'e', 'f', 'g');

        $result = $pluck->filter($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsThatPass(): array
    {
        return [
            [
                ['from' => ['a' => 1, 'b' => 2, 'c' => 3], 'd' => 4],
                ['from' => ['a' => 1, 'b' => 2, 'c' => 3], 'a' => 1, 'c' => 3, 'd' => 4],
                'from',
                'a',
                'c',
            ],
            [
                ['from' => ['a' => 1, 'b' => 2, 'c' => 3], 'a' => 5, 'd' => 4],
                ['from' => ['a' => 1, 'b' => 2, 'c' => 3], 'a' => 1, 'd' => 4],
                'from',
                'a',
                'd',
            ],
            [
                ['from' => ['a' => 1, 'b' => 2, 'c' => 3], 'd' => 4],
                ['from' => ['a' => 1, 'b' => 2, 'c' => 3], 'a' => 1, 'd' => 4],
                'from',
                'a',
                'd',
            ],
        ];
    }

    #[DataProvider('dataSetsThatPass')]
    #[Test]
    public function correctInputsSuccessfullyPluckValue($input, $expectedValue, $fieldSet, ...$fieldNames): void
    {
        $expected = Result::noResult($expectedValue);
        $pluck = new Pluck($fieldSet, ...$fieldNames);

        $result = $pluck->filter($input);

        self::assertEquals($expected, $result);
    }
}
