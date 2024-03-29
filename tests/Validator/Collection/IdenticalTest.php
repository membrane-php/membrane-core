<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\Collection;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Collection\Identical;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Identical::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class IdenticalTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'contains only identical values';
        $sut = new Identical();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new Identical();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsWithIncorrectTypes(): array
    {
        return [
            [123, 'integer'],
            [1.23, 'double'],
            ['string', 'string'],
            [true, 'boolean'],
            [null, 'NULL'],
        ];
    }

    #[DataProvider('dataSetsWithIncorrectTypes')]
    #[Test]
    public function incorrectTypesReturnInvalidResults($input, $expectedVars): void
    {
        $identical = new Identical();
        $expected = Result::invalid(
            $input,
            new MessageSet(
                null,
                new Message('Identical Validator requires an array, %s given', [$expectedVars])
            )
        );

        $result = $identical->validate($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsForValidResults(): array
    {
        return [
            [[]],
            [[[], []]],
            [[[1, 5], [1, 5]]],
            [[1]],
            [[1, 1]],
            [[null, null]],
            [[25, 5 * 5, 100 / 4, 20 + 5]],
        ];
    }

    #[DataProvider('dataSetsForValidResults')]
    #[Test]
    public function returnsValidIfEveryInputIsIdentical(mixed $input): void
    {
        $expected = Result::valid($input);
        $identical = new Identical();

        $result = $identical->validate($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsForInvalidResults(): array
    {
        return [
            [[null, 1], Result::INVALID],
            [[1, 1.0], Result::INVALID],
            [[1, '1'], Result::INVALID],
            [[true, false], Result::INVALID],
            [[true, 'true'], Result::INVALID],
            [[false, ''], Result::INVALID],
            [[null, ''], Result::INVALID],
            [[1, 1, 2], Result::INVALID],
            [[1, 2, 3, 4, 5], Result::INVALID],
            [[[1, 5], [5, 1]], Result::INVALID],
        ];
    }

    #[DataProvider('dataSetsForInvalidResults')]
    #[Test]
    public function returnsInvalidIfAnyInputIsDifferent(mixed $input): void
    {
        $expected = Result::invalid($input, new MessageSet(null, new Message('Do not match', [])));
        $identical = new Identical();

        $result = $identical->validate($input);

        self::assertEquals($expected, $result);
    }
}
