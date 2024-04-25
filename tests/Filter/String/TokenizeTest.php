<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\String;

use Membrane\Exception\InvalidFilterArguments;
use Membrane\Filter\String\Tokenize;
use Membrane\Renderer\HumanReadable;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Tests\MembraneTestCase;
use Membrane\Tests\Renderer\HumanReadableTest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(Tokenize::class)]
#[CoversClass(InvalidFilterArguments::class)]
#[UsesClass(HumanReadable::class)] // to render test failure messages
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class TokenizeTest extends MembraneTestCase
{
    #[Test, TestDox('It cannot have an empty string delimiter or it will throw an Exception')]
    public function throwsExceptionForEmptyStringDelimiters(): void
    {
        self::expectExceptionObject(InvalidFilterArguments::emptyStringDelimiter());

        new Tokenize('');
    }

    #[Test, TestDox('__toString returns a description of what the filter is going to do')]
    #[DataProvider('provideDelimiters')]
    public function itConvertsToStringOfBehaviour(string $delimiters): void
    {
        $sut = new Tokenize($delimiters);

        self::assertSame(
            sprintf('Tokenize string using "%s" as a delimiter', $delimiters),
            $sut->__toString()
        );
    }

    #[Test, TestDox('__toPHP returns evaluable string of PHP code for instantiating itself')]
    #[DataProvider('provideDelimiters')]
    public function itConvertsToCodeOfSelf(string $delimiter): void
    {
        $sut = new Tokenize($delimiter);
        self::assertInstanceOf(Tokenize::class, eval('return ' . $sut->__toPHP() . ';'));
    }

    #[Test, TestDox('It returns invalid results if the value being filtered is not a string')]
    #[DataProvider('provideCasesThatAreNotStrings')]
    public function filterReturnsInvalidForNonStringValues(mixed $value): void
    {
        $expected = Result::invalid($value, new MessageSet(null, new Message(
            'Tokenize Filter expects string, %s given',
            [gettype($value)]
        )));

        $sut = new Tokenize(',');

        self::assertResultEquals($expected, $sut->filter($value));
    }

    #[Test, TestDox('It filters comma-delimited strings into lists of values')]
    #[DataProvider('provideCasesOfDelimitedStrings')]
    public function itTokenizesStringsToLists(string $delimiter, mixed $value, Result $expected): void
    {
        $sut = new Tokenize($delimiter);
        self::assertEquals($expected, $sut->filter($value));
    }

    public static function provideDelimiters(): array
    {
        return [
            'space' => [' '],
            'comma' => [','],
            'semi-colon' => [';'],
        ];
    }

    public static function provideCasesThatAreNotStrings(): array
    {
        return [
            'integer' => [123],
            'float' => [1.23],
            'bool' => [true],
            'null' => [null],
            'array' => [['1,2,3', '4,5,6']],
        ];
    }

    public static function provideCasesOfDelimitedStrings(): array
    {
        return [
            '(comma-delimited) nothing but commas' => [
                ',',
                ',,,',
                Result::noResult([]),
            ],
            '(space-delimited) nothing but commas' => [
                ' ',
                ',,,',
                Result::noResult([',,,']),
            ],
            '(comma-delimited) five characters, seperated by commas' => [
                ',',
                'a,2,c,4,!',
                Result::noResult(['a', '2', 'c', '4', '!']),
            ],
            '(space-delimited) five characters, seperated by commas' => [
                ' ',
                'a,2,c,4,!',
                Result::noResult(['a,2,c,4,!']),
            ],
            '(comma-delimited) five characters, seperated by spaces' => [
                ',',
                'a 2 c 4 !',
                Result::noResult(['a 2 c 4 !']),
            ],
            '(space-delimited) five characters, seperated by spaces' => [
                ' ',
                'a 2 c 4 !',
                Result::noResult(['a', '2', 'c', '4', '!']),
            ],
            '(comma and space delimited) separated by a comma and space' => [
                ' ,',
                'a, 2, c, 4, !',
                Result::noResult(['a', '2', 'c', '4', '!']),
            ],
            '(comma and space delimited) separated by a period and space' => [
                ' ,',
                'a. 2. c. 4. !',
                Result::noResult(['a.', '2.', 'c.', '4.', '!']),
            ],
        ];
    }
}
