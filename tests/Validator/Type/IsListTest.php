<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IsList::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class IsListTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'is a list';
        $sut = new IsList();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new IsList();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsThatPass(): array
    {
        return [
            [['this', 'is', 'a', 'list']],
            [[]],
        ];
    }

    #[DataProvider('dataSetsThatPass')]
    #[Test]
    public function listReturnsValid($input): void
    {
        $isList = new IsList();
        $expected = Result::valid($input);

        $result = $isList->validate($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsThatAreNotArraysOrLists(): array
    {
        return [
            ['true', 'string'],
            [1, 'integer'],
            [1.1, 'double'],
            [false, 'boolean'],
            [null, 'NULL'],
        ];
    }

    #[DataProvider('dataSetsThatAreNotArraysOrLists')]
    #[Test]
    public function typesThatAreNotArraysOrListsReturnInvalid($input, $expectedVar): void
    {
        $isList = new IsList();
        $expectedMessage = new Message(
            'IsList validator expects list value, %s passed instead',
            [$expectedVar]
        );
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $isList->validate($input);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function arrayReturnsInvalid(): void
    {
        $input = ['a' => 'this is', 'b' => 'an array'];
        $expectedMessage = new Message(
            'IsList validator expects list value, lists do not have keys',
            []
        );
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $isList = new IsList();

        $result = $isList->validate($input);

        self::assertEquals($expected, $result);
    }
}
