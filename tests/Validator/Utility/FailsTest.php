<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\Utility;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Utility\Fails;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Fails::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class FailsTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'will return invalid';
        $sut = new Fails();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new Fails();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSets(): array
    {
        return [[1], [1.1], ['one'], [true], [null],];
    }

    #[DataProvider('dataSets')]
    #[Test]
    public function failsAlwaysReturnsInvalid(mixed $input): void
    {
        $expected = Result::invalid($input, new MessageSet(null, new Message('I always fail', [])));
        $sut = new Fails();

        $result = $sut->validate($input);

        self::assertEquals($expected, $result);
    }
}
