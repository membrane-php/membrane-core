<?php

declare(strict_types=1);

namespace Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsArray;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IsArray::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class IsArrayTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'is an array with string keys';
        $sut = new IsArray();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new IsArray();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsThatPass(): array
    {
        return [
            [['a' => 'arrays have', 'b' => 'string keys']],
            [[]],
        ];
    }

    #[DataProvider('dataSetsThatPass')]
    #[Test]
    public function arrayReturnsValid($input): void
    {
        $isArray = new IsArray();
        $expected = Result::valid($input);

        $result = $isArray->validate($input);

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
    public function typesThatAreNotArraysReturnInvalid($input, $expectedVar): void
    {
        $isArray = new IsArray();
        $expectedMessage = new Message(
            'IsArray validator expects array value, %s passed instead',
            [$expectedVar]
        );
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $isArray->validate($input);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function listsReturnInvalid(): void
    {
        $input = ['this', 'is', 'a', 'list'];
        $expectedMessage = new Message('IsArray validator expects array values with keys, list passed instead', []);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $isArray = new IsArray();

        $result = $isArray->validate($input);

        self::assertEquals($expected, $result);
    }
}
