<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\String;

use Generator;
use Membrane\Filter\String\LeftTrim;
use Membrane\Renderer\HumanReadable;
use Membrane\Result\{Message, MessageSet, Result};
use Membrane\Tests\MembraneTestCase;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test, TestDox, UsesClass};

#[CoversClass(LeftTrim::class)]
#[UsesClass(HumanReadable::class)] // to render test failure messages
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
class LeftTrimTest extends MembraneTestCase
{
    #[Test, TestDox('toString method will describe what the Filter does')]
    #[DataProvider('provideCharacters')]
    public function toStringTest(string $characters): void
    {
        $expected = sprintf(
            'Trim "%s" off the left side of the string value',
            $characters,
        );

        self::assertSame($expected, (string) new LeftTrim($characters));
    }

    #[Test, TestDox('__toPHP() will return evaluable PHP code as a string')]
    #[DataProvider('provideCharacters')]
    public function toPHPTest(string $characters): void
    {
        $expected = new LeftTrim($characters);

        $actual = eval('return ' . $expected->__toPHP() . ';');

        self::assertEquals($expected, $actual);
    }

    #[Test, TestDox('It will return an invalid Result if it filters values that are not strings')]
    public function returnsInvalidResultForNonStringValues(): void
    {
        $nonStringValue = 5;
        $expected = Result::invalid(
            $nonStringValue,
            new MessageSet(
                null,
                new Message('LeftTrim Filter expects string value, %s given', [gettype($nonStringValue)])
            )
        );

        self::assertEquals($expected, (new LeftTrim(''))->filter($nonStringValue));
    }


    #[Test, TestDox('It filters strings to contain only alphanumeric characters')]
    #[DataProvider('provideStringsToFilter')]
    public function itLeftTrimsCharacters(string $characters, string $value, Result $expected): void
    {
        $sut = new LeftTrim($characters);

        self::assertResultEquals($expected, $sut->filter($value));
    }

    /** @return Generator<array{ 0: string}> */
    public static function provideCharacters(): Generator
    {
        yield '.' => ['.'];
        yield '/' => ['/'];
        yield 'abcdefghijklmnopqrstuvwxyz' => ['abcdefghijklmnopqrstuvwxyz'];
    }

    /** @return Generator<array{ 0: string, 1: string, 2: Result}> */
    public static function provideStringsToFilter(): Generator
    {
        yield 'trim "." from ".blue"' => [
            '.',
            '.blue',
            Result::noResult('blue'),
        ];

        yield 'trim "." from ".blue.black.brown"' => [
            '.',
            '.blue.black.brown',
            Result::noResult('blue.black.brown'),
        ];

        yield 'trim ";" from ";colour=blue"' => [
            ';',
            ';colour=blue',
            Result::noResult('colour=blue'),
        ];

        yield 'trim ";" from ";colour=blue,black,brown"' => [
            ';',
            ';colour=blue,black,brown',
            Result::noResult('colour=blue,black,brown'),
        ];
    }
}
