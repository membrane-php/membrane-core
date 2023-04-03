<?php

declare(strict_types=1);

namespace Filter\String;

use Membrane\Filter\String\CommaDelimited;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CommaDelimited::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
class CommaDelimitedTest extends TestCase
{
    private CommaDelimited $sut;

    public function setUp(): void
    {
        $this->sut = new CommaDelimited();
    }

    #[Test, TestDox('__toString returns a description of what the filter is going to do')]
    public function toStringReturnsDescriptionOfBehaviour(): void
    {
        self::assertSame('seperate comma-delimited string into a list of values', $this->sut->__toString());
    }

    #[Test, TestDox('__toPHP returns evaluable string of PHP code for instantiating itself')]
    public function toPHPReturnsEvaluablePHPStringForNewInstanceOfSelf(): void
    {
        self::assertInstanceOf(CommaDelimited::class, eval('return ' . $this->sut->__toPHP() . ';'));
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
                        new Message('CommaDelimited Filter expects a string value, %s passed instead', ['integer'])
                    )
                ),
            ],
            'a float value' => [
                1.23,
                Result::invalid(
                    1.23,
                    new MessageSet(
                        null,
                        new Message('CommaDelimited Filter expects a string value, %s passed instead', ['double'])
                    )
                ),
            ],
            'a bool value' => [
                true,
                Result::invalid(
                    true,
                    new MessageSet(
                        null,
                        new Message('CommaDelimited Filter expects a string value, %s passed instead', ['boolean'])
                    )
                ),
            ],
            'a null value' => [
                null,
                Result::invalid(
                    null,
                    new MessageSet(
                        null,
                        new Message('CommaDelimited Filter expects a string value, %s passed instead', ['NULL'])
                    )
                ),
            ],
            'an array value' => [
                ['1,2,3', '4,5,6'],
                Result::invalid(
                    ['1,2,3', '4,5,6'],
                    new MessageSet(
                        null,
                        new Message('CommaDelimited Filter expects a string value, %s passed instead', ['array'])
                    )
                ),
            ],

        ];
    }

    public static function provideCasesOfCommaDelimitedStrings(): array
    {
        return [
            'a string of nothing but commas' => [
                ',,,',
                Result::noResult(['', '', '', '']),
            ],
            'single character, no commas' => [
                '1',
                Result::noResult(['1']),
            ],
            'two characters, seperated by a space' => [
                '1 2',
                Result::noResult(['1 2']),
            ],
            'two characters, seperated by a comma' => [
                '1,2',
                Result::noResult(['1', '2']),
            ],
            'five characters, seperated by commas' => [
                'a,2,c,4,!',
                Result::noResult(['a', '2', 'c', '4', '!']),
            ],
        ];
    }

    #[Test, TestDox('It filters comma-delimited strings into lists of values')]
    #[DataProvider('provideCasesOfCommaDelimitedStrings'), DataProvider('provideCasesThatAreNotStrings')]
    public function FiltersCommaDelimitedStringsIntoLists(mixed $value, Result $expected): void
    {
        self::assertEquals($expected, $this->sut->filter($value));
    }
}
