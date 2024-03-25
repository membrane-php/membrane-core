<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\Shape;

use Membrane\Filter\Shape\Collect;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Collect::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class CollectTest extends TestCase
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
                'collect "a" from self and append their values to a nested collection "new collection"',
            ],
            'multiple fields' => [
                ['a', 'b', 'c'],
                'collect "a", "b", "c" from self and append their values to a nested collection "new collection"',
            ],
        ];
    }

    #[DataProvider('dataSetsToConvertToString')]
    #[Test]
    public function toStringTest(array $fields, string $expected): void
    {
        $sut = new Collect('new collection', ...$fields);

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    public static function dataSetsToConvertToPHPString(): array
    {
        return [
            'no fields' => [new Collect('collection')],
            'one field' => [new Collect('collection', 'a')],
            'multiple fields' => [new Collect('collection', 'a', 'b', 'c')],
        ];
    }

    #[DataProvider('dataSetsToConvertToPHPString')]
    #[Test]
    public function toPHPTest(Collect $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsWithIncorrectTypes(): array
    {
        return [
            [
                'this is a string',
                'Value passed to Collect filter must be an array, %s passed instead',
                ['string'],
            ],
            [
                ['this', 'is', 'a', 'list'],
                'Value passed to Collect filter was a list, this filter needs string keys to work',
                [],
            ],
        ];
    }

    #[DataProvider('dataSetsWithIncorrectTypes')]
    #[Test]
    public function incorrectFilterInputReturnsInvalid(mixed $input, string $expectedMessage, array $expectedVars): void
    {
        $collect = new Collect('new field', 'a', 'b');
        $expected = Result::invalid($input, new MessageSet(null, new Message($expectedMessage, $expectedVars)));

        $result = $collect->filter($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsWithCorrectTypes(): array
    {
        return [
            'collecting all items in array' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                ['a', 'b', 'c'],
                ['new field' => [1, 2, 3]],
            ],
            'collecting 2 out of 3 items in array' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                ['a', 'b'],
                ['new field' => [1, 2], 'c' => 3],
            ],
            'collecting an item that is not in the array' => [
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
        $collect = new Collect('new field', ...$fieldsToCollect);
        $expected = Result::noResult($expectedValue);

        $result = $collect->filter($input);

        self::assertEquals($expected, $result);
    }
}
