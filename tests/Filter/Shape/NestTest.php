<?php

declare(strict_types=1);

namespace Filter\Shape;

use Membrane\Filter\Shape\Nest;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Nest::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class NestTest extends TestCase
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
                'collect "a" from self and append them to a nested field set "new field set"',
            ],
            'multiple fields' => [
                ['a', 'b', 'c'],
                'collect "a", "b", "c" from self and append them to a nested field set "new field set"',
            ],
        ];
    }

    #[DataProvider('dataSetsToConvertToString')]
    #[Test]
    public function toStringTest(array $fields, string $expected): void
    {
        $sut = new Nest('new field set', ...$fields);

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    public static function dataSetsToConvertToPHPString(): array
    {
        return [
            'no fields' => [new Nest('new field'),],
            'one field' => [new Nest('new field', 'a'),],
            'multiple fields' => [new Nest('new field', 'a', 'b', 'c'),],
        ];
    }

    #[DataProvider('dataSetsToConvertToPHPString')]
    #[Test]
    public function toPHPTest(Nest $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsWithIncorrectTypes(): array
    {
        return [
            [
                'this is a string',
                'Value passed to Nest filter must be an array, %s passed instead',
                ['string'],
            ],
            [
                ['this', 'is', 'a', 'list'],
                'Value passed to Nest filter was a list, this filter needs string keys to work',
                [],
            ],
        ];
    }

    #[DataProvider('dataSetsWithIncorrectTypes')]
    #[Test]
    public function incorrectFilterInputReturnsInvalid(mixed $input, string $expectedMessage, array $expectedVars): void
    {
        $nest = new Nest('new field', 'a', 'b');
        $expected = Result::invalid($input, new MessageSet(null, new Message($expectedMessage, $expectedVars)));

        $result = $nest->filter($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsWithCorrectTypes(): array
    {
        return [
            'nesting all items in array' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                ['a', 'b', 'c'],
                ['new field' => ['a' => 1, 'b' => 2, 'c' => 3]],
            ],
            'nesting 2 out of 3 items in array' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                ['a', 'b'],
                ['new field' => ['a' => 1, 'b' => 2], 'c' => 3],
            ],
            'nesting an item that is not in the array' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                ['d'],
                ['new field' => [], 'a' => 1, 'b' => 2, 'c' => 3],
            ],
        ];
    }

    #[DataProvider('dataSetsWithCorrectTypes')]
    #[Test]
    public function correctFilterInputReturnsResult(array $input, array $fieldsToCollect, array $expectedValue): void
    {
        $nest = new Nest('new field', ...$fieldsToCollect);
        $expected = Result::noResult($expectedValue);

        $result = $nest->filter($input);

        self::assertEquals($expected, $result);
    }
}
