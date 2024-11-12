<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\Shape;

use Membrane\Filter\Shape\KeyValueSplit;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(KeyValueSplit::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class KeyValueSplitTest extends TestCase
{
    #[Test, TestDox('toString returns a plain english description of what this filter accomplishes')]
    public function toStringTest(): void
    {
        $expected = 'split list into keys and values, then combine them into an array';
        $sut = new KeyValueSplit();

        self::assertSame($expected, (string)$sut);
    }

    public static function provideInstancesOfKeyValueSplit(): array
    {
        return [
            'instance with $keysFirst = true' => [new KeyValueSplit(true)],
            'instance with $keysFirst = false' => [new KeyValueSplit(false)],
        ];
    }

    #[Test, TestDox('toPHP returns a string of PHP code that evaluates to an instance of itself')]
    #[DataProvider('provideInstancesOfKeyValueSplit')]
    public function toPHPReturnsEvaluablePHPStringToCreateSelf(KeyValueSplit $sut): void
    {
        self::assertEquals($sut, eval('return ' . $sut->__toPHP() . ';'));
    }

    public static function provideListsToFilter(): array
    {
        return [
            'invalid result for values that are not lists' => [
                true,
                'string value',
                Result::invalid(
                    'string value',
                    new MessageSet(
                        null,
                        new Message('KeyValueSplit Filter expects a list value, %s passed instead', ['string'])
                    )
                ),
            ],
            'invalid result for values that are arrays instead of lists' => [
                true,
                ['a' => 'an', 'b' => 'array'],
                Result::invalid(
                    ['a' => 'an', 'b' => 'array'],
                    new MessageSet(
                        null,
                        new Message('KeyValueSplit Filter expects a list value, %s passed instead', ['array'])
                    )
                ),
            ],
            'invalid result for lists with an odd number of values' => [
                true,
                ['a', 'b', 'c'],
                Result::invalid(
                    ['a', 'b', 'c'],
                    new MessageSet(
                        null,
                        new Message('KeyValueSplit requires a list with an even number of values', [])
                    )
                ),
            ],
            'valid result for minimal example (keys first)' => [
                true,
                ['a', 'one'],
                Result::valid(['a' => 'one']),
            ],
            'valid result for minimal example (keys second)' => [
                false,
                ['a', 'one'],
                Result::valid(['one' => 'a']),
            ],
            'valid result for large example (keys first)' => [
                true,
                ['a', 'one', 'b', 'two', 'c', 'three'],
                Result::valid(['a' => 'one', 'b' => 'two', 'c' => 'three']),
            ],
            'valid result for large example (keys second)' => [
                false,
                ['a', 'one', 'b', 'two', 'c', 'three'],
                Result::valid(['one' => 'a', 'two' => 'b', 'three' => 'c']),
            ],

        ];
    }

    #[Test, TestDox('filter splits the list into two, then combines them into a key-value array')]
    #[DataProvider('provideListsToFilter')]
    public function filterSplitsListsAndCombinesIntoArray(bool $keysFirst, mixed $value, Result $expected): void
    {
        $sut = new KeyValueSplit($keysFirst);

        $actual = $sut->filter($value);

        self::assertEquals($expected, $actual);
    }
}
