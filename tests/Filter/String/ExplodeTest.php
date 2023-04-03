<?php

declare(strict_types=1);

namespace Filter\String;

use Membrane\Exception\InvalidFilterArguments;
use Membrane\Filter\String\Explode;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Explode::class)]
#[CoversClass(InvalidFilterArguments::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class ExplodeTest extends TestCase
{
    public function throwsExceptionIfEmptyStringUsedAsDelimiter(): void
    {
        self::expectExceptionObject(InvalidFilterArguments::emptyStringDelimiter());

        new Explode('');
    }

    public static function provideDelimiters(): array
    {
        return [
            'space' => [' '],
            'comma' => [','],
            'semi-colon' => [';'],
        ];
    }

    #[Test, TestDox('__toString returns a description of what the filter is going to do')]
    #[DataProvider('provideDelimiters')]
    public function toStringReturnsDescriptionOfBehaviour(string $delimiter): void
    {
        $sut = new Explode($delimiter);
        self::assertSame(sprintf('explode string value using "%s" as a delimiter', $delimiter), $sut->__toString());
    }

    #[Test, TestDox('__toPHP returns evaluable string of PHP code for instantiating itself')]
    #[DataProvider('provideDelimiters')]
    public function toPHPReturnsEvaluablePHPStringForNewInstanceOfSelf(string $delimiter): void
    {
        $sut = new Explode($delimiter);
        self::assertInstanceOf(Explode::class, eval('return ' . $sut->__toPHP() . ';'));
    }

    public static function provideCasesThatAreNotStrings(): array
    {
        return [
            'an integer value' => [
                123,
                Result::invalid(
                    123,
                    new MessageSet(
                        null,
                        new Message('Explode Filter expects a string value, %s passed instead', ['integer'])
                    )
                ),
            ],
            'a float value' => [
                1.23,
                Result::invalid(
                    1.23,
                    new MessageSet(
                        null,
                        new Message('Explode Filter expects a string value, %s passed instead', ['double'])
                    )
                ),
            ],
            'a bool value' => [
                true,
                Result::invalid(
                    true,
                    new MessageSet(
                        null,
                        new Message('Explode Filter expects a string value, %s passed instead', ['boolean'])
                    )
                ),
            ],
            'a null value' => [
                null,
                Result::invalid(
                    null,
                    new MessageSet(
                        null,
                        new Message('Explode Filter expects a string value, %s passed instead', ['NULL'])
                    )
                ),
            ],
            'an array value' => [
                ['1,2,3', '4,5,6'],
                Result::invalid(
                    ['1,2,3', '4,5,6'],
                    new MessageSet(
                        null,
                        new Message('Explode Filter expects a string value, %s passed instead', ['array'])
                    )
                ),
            ],

        ];
    }

    #[Test, TestDox('It returns invalid results if the value being filtered is not a string')]
    #[DataProvider('provideCasesThatAreNotStrings')]
    public function filterReturnsInvalidForNonStringValues(mixed $value, Result $expected): void
    {
        $sut = new Explode(',');
        self::assertEquals($expected, $sut->filter($value));
    }

    public static function provideCasesOfDelimitedStrings(): array
    {
        return [
            '(comma-delimited) nothing but commas' => [
                ',',
                ',,,',
                Result::noResult(['', '', '', '']),
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
        ];
    }

    #[Test, TestDox('It filters comma-delimited strings into lists of values')]
    #[DataProvider('provideCasesOfDelimitedStrings')]
    public function FiltersCommaDelimitedStringsIntoLists(string $delimiter, mixed $value, Result $expected): void
    {
        $sut = new Explode($delimiter);
        self::assertEquals($expected, $sut->filter($value));
    }
}
