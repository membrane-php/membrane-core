<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\Utility;

use Membrane\Result\Result;
use Membrane\Validator\Utility\Indifferent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Indifferent::class)]
#[UsesClass(Result::class)]
class IndifferentTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = '';
        $sut = new Indifferent();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new Indifferent();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSets(): array
    {
        return [[1], [1.1], ['one'], [false], [null],];
    }

    #[DataProvider('dataSets')]
    #[Test]
    public function indifferentAlwaysReturnsNoResult(mixed $input): void
    {
        $sut = new Indifferent();
        $expected = Result::noResult($input);

        $result = $sut->validate($input);

        self::assertEquals($expected, $result);
    }
}
