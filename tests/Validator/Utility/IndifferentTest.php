<?php

declare(strict_types=1);

namespace Validator\Utility;

use Membrane\Result\Result;
use Membrane\Validator\Utility\Indifferent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Utility\Indifferent
 * @uses   \Membrane\Result\Result
 */
class IndifferentTest extends TestCase
{
    /** @test */
    public function toStringTest(): void
    {
        $expected = '';
        $sut = new Indifferent();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    /** @test */
    public function toPHPTest(): void
    {
        $sut = new Indifferent();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public function dataSets(): array
    {
        return [[1], [1.1], ['one'], [false], [null],];
    }

    /**
     * @test
     * @dataProvider dataSets
     */
    public function indifferentAlwaysReturnsNoResult(mixed $input): void
    {
        $sut = new Indifferent();
        $expected = Result::noResult($input);

        $result = $sut->validate($input);

        self::assertEquals($expected, $result);
    }
}
