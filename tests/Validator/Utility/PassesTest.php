<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\Utility;

use Membrane\Result\Result;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Passes::class)]
#[UsesClass(Result::class)]
class PassesTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'will return valid';
        $sut = new Passes();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new Passes();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSets(): array
    {
        return [[1], [1.1], ['one'], [false], [null],];
    }

    #[DataProvider('dataSets')]
    #[Test]
    public function passesAlwaysReturnsValid(mixed $input): void
    {
        $expected = Result::valid($input);
        $sut = new Passes();

        $result = $sut->validate($input);

        self::assertEquals($expected, $result);
    }
}
