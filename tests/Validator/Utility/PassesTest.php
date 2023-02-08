<?php

declare(strict_types=1);

namespace Validator\Utility;

use Membrane\Result\Result;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Utility\Passes
 * @uses   \Membrane\Result\Result
 */
class PassesTest extends TestCase
{
    /** @test */
    public function toStringTest(): void
    {
        $expected = 'will return valid';
        $sut = new Passes();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    public function dataSets(): array
    {
        return [[1], [1.1], ['one'], [false], [null],];
    }

    /**
     * @test
     * @dataProvider dataSets
     */
    public function passesAlwaysReturnsValid(mixed $input): void
    {
        $expected = Result::valid($input);
        $sut = new Passes();

        $result = $sut->validate($input);

        self::assertEquals($expected, $result);
    }
}
