<?php

declare(strict_types=1);

namespace Membrane\Tests\Processor;

use Membrane\Exception\InvalidProcessorArguments;
use Membrane\Filter\Shape\Truncate;
use Membrane\Filter\Type\ToFloat;
use Membrane\Processor;
use Membrane\Processor\AfterSet;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Processor\Field;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Collection\Count;
use Membrane\Validator\Type\IsFloat;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Collection::class)]
#[CoversClass(InvalidProcessorArguments::class)]
#[UsesClass(Truncate::class)]
#[UsesClass(ToFloat::class)]
#[UsesClass(BeforeSet::class)]
#[UsesClass(Field::class)]
#[UsesClass(AfterSet::class)]
#[UsesClass(FieldName::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
#[UsesClass(Count::class)]
#[UsesClass(IsFloat::class)]
#[UsesClass(Passes::class)]
#[UsesClass(Indifferent::class)]
#[UsesClass(Fails::class)]
class CollectionTest extends TestCase
{
    public static function dataSetsToConvertToString(): array
    {
        return [
            'No chain returns empty string' => [
                '',
                new Collection('a'),
            ],
            'Chain with no conditions returns empty string' => [
                '',
                new Collection('a', new Field('')),
            ],
            'Chain with guaranteed noResult returns empty string' => [
                '',
                new Collection('a', new Field('', new Indifferent())),
            ],
            'Chain with conditions but processes empty string returns empty string' => [
                '',
                new Collection('', new Field('', new Passes())),
            ],
            'Chain with one condition returns one bullet point' => [
                "Each field in \"a\":\n\t- will return valid.",
                new Collection('a', new Field('', new Passes())),
            ],
            'Chain with three condition returns three bullet points' => [
                "Each field in \"a\":\n\t- will return valid.\n\t- will return valid.\n\t- will return invalid.",
                new Collection('a', new Field('', new Passes(), new Passes(), new Fails())),
            ],
            'BeforeSet adds a Firstly: section' => [
                "Firstly \"a\":\n\t- will return valid.",
                new Collection('a', new BeforeSet(new Passes())),
            ],
            'AfterSet adds a Lastly: section' => [
                "Lastly \"a\":\n\t- will return valid.",
                new Collection('a', new AfterSet(new Passes())),
            ],
            'Chain with BeforeSet, Field and AfterSet' => [
                <<<END
                Firstly "a":
                \t- will return invalid.
                Each field in "a":
                \t- will return valid.
                Lastly "a":
                \t- will return valid.
                END,
                new Collection(
                    'a',
                    new BeforeSet(new Fails()),
                    new Field('', new Passes()),
                    new AfterSet(new Passes())
                ),
            ],
        ];
    }

    #[DataProvider('dataSetsToConvertToString')]
    #[Test]
    public function toStringTest(string $expected, Collection $sut): void
    {
        $actual = (string)$sut;

        self::assertSame($expected, $actual);
    }

    public static function dataSetsToConvertToPHPString(): array
    {
        return [
            'no chain' => [
                new Collection('a'),
            ],
            '1 empty Field' => [
                new Collection('b', new Field('')),
            ],
            '1 Field' => [
                new Collection('c', new Field('', new Passes())),
            ],
            '1 BeforeSet' => [
                new Collection('d', new BeforeSet(new Passes())),
            ],
            '1 AfterSet' => [
                new Collection('e', new AfterSet(new Passes())),
            ],
            '1 Field, 1 BeforeSet, 1 AfterSet, 1 DefaultProcessor' => [
                new Collection(
                    'f',
                    new Field('', new Fails()),
                    new BeforeSet(new Passes()),
                    new AfterSet(new Fails()),
                ),
            ],
        ];
    }

    #[DataProvider('dataSetsToConvertToPHPString')]
    #[Test]
    public function toPHPTest(Collection $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsWithIncorrectValues(): array
    {
        $notArrayMessage = 'Value passed to %s in Collection chain must be a list, %s passed instead';
        return [
            [1, new Message($notArrayMessage, ['Membrane\Processor\Field', 'integer'])],
            [2.0, new Message($notArrayMessage, ['Membrane\Processor\Field', 'double'])],
            ['string', new Message($notArrayMessage, ['Membrane\Processor\Field', 'string'])],
            [true, new Message($notArrayMessage, ['Membrane\Processor\Field', 'boolean'])],
            [null, new Message($notArrayMessage, ['Membrane\Processor\Field', 'NULL'])],
            [['a' => 1, 'b' => 2, 'c' => 3], new Message($notArrayMessage, ['Membrane\Processor\Field', 'array'])],
        ];
    }

    #[DataProvider('dataSetsWithIncorrectValues')]
    #[Test]
    public function onlyAcceptsArrayValues(mixed $input, Message $expectedMessage): void
    {
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $fieldName = 'field to process';
        $sut = new Collection($fieldName, new Field(''));

        $result = $sut->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function onlyAcceptsOneField(): void
    {
        $field = new Field('field to process');
        self::expectExceptionObject(InvalidProcessorArguments::multipleProcessorsInCollection());

        new Collection('field to process', $field, $field);
    }

    #[Test]
    public function processesTest(): void
    {
        $fieldName = 'field to process';
        $sut = new Collection($fieldName);

        $output = $sut->processes();

        self::assertEquals($fieldName, $output);
    }

    #[Test]
    public function processMethodWithNoChainReturnsNoResult(): void
    {
        $value = [];
        $expected = Result::noResult($value);
        $sut = new Collection('field to process');

        $result = $sut->process(new FieldName('Parent field'), $value);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsOfFields(): array
    {
        return [
            'No chain returns noResult' => [
                [1, 2, 3],
                Result::noResult([1, 2, 3]),
            ],
            'Return valid result' => [
                [1, 2, 3],
                Result::valid([1, 2, 3]),
                new Field('a', new Passes()),
            ],
            'Return noResult' => [
                [1, 2, 3],
                Result::noResult([1, 2, 3]),
                new Field('b', new Indifferent()),
            ],
            'Return invalid result' => [
                [1, 2, 3],
                Result::invalid(
                    [1, 2, 3],
                    new MessageSet(
                        new FieldName('c', 'parent field', 'field to process', '0'),
                        new Message('I always fail', [])
                    ),
                    new MessageSet(
                        new FieldName('c', 'parent field', 'field to process', '1'),
                        new Message('I always fail', [])
                    ),
                    new MessageSet(
                        new FieldName('c', 'parent field', 'field to process', '2'),
                        new Message('I always fail', [])
                    )
                ),
                new Field('c', new Fails()),
            ],
            'Field processes every item in array' => [
                [1, 2, 3],
                Result::noResult([1.0, 2.0, 3.0]),
                new Field('d', new ToFloat()),
            ],
            'Field processed values persist' => [
                [1, 2, 3],
                Result::valid([1.0, 2.0, 3.0]),
                new Field('e', new ToFloat(), new IsFloat()),
            ],
            'BeforeSet processes before Field' => [
                [1.0, 2.0, 3.0],
                Result::noResult([]),
                new BeforeSet(new Truncate(0)),
                new Field('f', new IsFloat()),
            ],
            'BeforeSet processes before AfterSet' => [
                [1, 2, 3],
                Result::invalid(
                    [1, 2],
                    new MessageSet(
                        new FieldName('', 'parent field', 'field to process'),
                        new Message('Array is expected have a minimum of %d values', [3])
                    )
                ),
                new BeforeSet(new Truncate(2)),
                new AfterSet(new Count(3)),
            ],
            'AfterSet does not process if BeforeSet returns invalid' => [
                [1, 2, 3],
                Result::invalid(
                    [1, 2, 3],
                    new MessageSet(
                        new FieldName('', 'parent field', 'field to process'),
                        new Message('Array is expected have a minimum of %d values', [4])
                    )
                ),
                new BeforeSet(new Count(4)),
                new AfterSet(new Truncate(2)),
            ],
            'AfterSet processes after Field' => [
                [1.0, 2.0, 3.0],
                Result::valid([]),
                new Field('i', new IsFloat()),
                new AfterSet(new Truncate(0)),
            ],
            'BeforeSet then Field then AfterSet' => [
                [1, 2, 3],
                Result::valid([]),
                new BeforeSet(new Truncate(0)),
                new Field('j', new IsFloat()),
                new AfterSet(new Count(0, 0)),
            ],
        ];
    }

    #[DataProvider('dataSetsOfFields')]
    #[Test]
    public function processTest(array $input, Result $expected, Processor ...$chain): void
    {
        $sut = new Collection('field to process', ...$chain);

        $actual = $sut->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $actual);
        self::assertSame($expected->value, $actual->value);
    }
}
