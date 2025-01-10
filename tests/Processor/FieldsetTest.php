<?php

declare(strict_types=1);

namespace Membrane\Tests\Processor;

use Membrane\Exception\InvalidProcessorArguments;
use Membrane\Filter\Shape\Rename;
use Membrane\Filter\Type\ToFloat;
use Membrane\Filter\Type\ToInt;
use Membrane\Filter\Type\ToString;
use Membrane\Processor;
use Membrane\Processor\AfterSet;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\DefaultProcessor;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Collection\Identical;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Type\IsFloat;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FieldSet::class)]
#[CoversClass(InvalidProcessorArguments::class)]
#[UsesClass(Identical::class)]
#[UsesClass(Rename::class)]
#[UsesClass(ToFloat::class)]
#[UsesClass(ToInt::class)]
#[UsesClass(ToString::class)]
#[UsesClass(AfterSet::class)]
#[UsesClass(BeforeSet::class)]
#[UsesClass(DefaultProcessor::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
#[UsesClass(Field::class)]
#[UsesClass(FieldName::class)]
#[UsesClass(RequiredFields::class)]
#[UsesClass(IsFloat::class)]
#[UsesClass(Fails::class)]
#[UsesClass(Indifferent::class)]
#[UsesClass(Passes::class)]
class FieldsetTest extends TestCase
{
    public static function dataSetsToConvertToString(): array
    {
        return [
            'No chain returns empty string' => [
                '',
                new FieldSet('field set'),
            ],
            'Chain with no conditions returns empty string' => [
                '',
                new FieldSet('field set', new Field('a')),
            ],
            'Chain with guaranteed noResult returns empty string' => [
                '',
                new FieldSet('field set', new Field('a', new Indifferent())),
            ],
            'FieldSet processes empty string returns empty string' => [
                '',
                new FieldSet('', new Field('a', new Passes())),
            ],
            'Chain processors that process empty strings are ignored' => [
                '',
                new FieldSet('field set', new Field('', new Passes())),
            ],
            'Chain with one condition returns one bullet point' => [
                "\"field set\"->\"a\":\n\t- will return valid.",
                new FieldSet('field set', new Field('a', new Passes())),
            ],
            'Chain with three condition returns three bullet points' => [
                "\"field set\"->\"a\":\n\t- will return valid.\n\t- will return valid.\n\t- will return invalid.",
                new FieldSet('field set', new Field('a', new Passes(), new Passes(), new Fails())),
            ],
            'BeforeSet adds a Firstly: section' => [
                "Firstly \"field set\":\n\t- will return valid.",
                new FieldSet('field set', new BeforeSet(new Passes())),
            ],
            'AfterSet adds a Lastly: section' => [
                "Lastly \"field set\":\n\t- will return valid.",
                new FieldSet('field set', new AfterSet(new Passes())),
            ],
            'DefaultProcessor adds a Any other fields in "field set": section' => [
                "Any other fields in \"field set\":\n\t- will return valid.",
                new FieldSet('field set', DefaultProcessor::fromFiltersAndValidators(new Passes())),
            ],
            'Chain with BeforeSet, Field and AfterSet' => [
                <<<END
                Firstly "field set":
                \t- will return invalid.
                "field set"->"a":
                \t- will return valid.
                Any other fields in "field set":
                \t- will return valid.
                Lastly "field set":
                \t- will return valid.
                END,
                new FieldSet(
                    'field set',
                    new BeforeSet(new Fails()),
                    new Field('a', new Passes()),
                    DefaultProcessor::fromFiltersAndValidators(new Passes()),
                    new AfterSet(new Passes())
                ),
            ],
        ];
    }

    #[DataProvider('dataSetsToConvertToString')]
    #[Test]
    public function toStringTest(string $expected, FieldSet $sut): void
    {
        $actual = (string)$sut;

        self::assertSame($expected, $actual);
    }

    public static function dataSetsToConvertToPHPString(): array
    {
        return [
            'no chain' => [new FieldSet('a')],
            '1 empty Field' => [new FieldSet('b', new Field(''))],
            '1 Field' => [new FieldSet('c', new Field('', new Passes()))],
            '1 BeforeSet' => [new FieldSet('d', new BeforeSet(new Passes()))],
            '1 AfterSet' => [new FieldSet('e', new AfterSet(new Passes()))],
            '1 DefaultProcessor' => [new FieldSet('f', DefaultProcessor::fromFiltersAndValidators(new Passes()))],
            '1 Field, 1 BeforeSet, 1 AfterSet, 1 DefaultProcessor' => [
                new FieldSet(
                    'g',
                    new Field('', new Fails()),
                    new BeforeSet(new Passes()),
                    new AfterSet(new Fails()),
                    DefaultProcessor::fromFiltersAndValidators(new Passes())
                ),
            ],
        ];
    }

    #[DataProvider('dataSetsToConvertToPHPString')]
    #[Test]
    public function toPHPTest(FieldSet $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsWithIncorrectValues(): array
    {
        $notArrayMessage = 'Value passed to FieldSet chain be an array, %s passed instead';
        $listMessage = 'Value passed to FieldSet chain must be an array, list passed instead';
        return [
            [1, new Message($notArrayMessage, ['integer'])],
            [2.0, new Message($notArrayMessage, ['double'])],
            ['string', new Message($notArrayMessage, ['string'])],
            [true, new Message($notArrayMessage, ['boolean'])],
            [null, new Message($notArrayMessage, ['NULL'])],
            [['a', 'b', 'c'], new Message($listMessage, [])],
        ];
    }

    #[DataProvider('dataSetsWithIncorrectValues')]
    #[Test]
    public function onlyAcceptsArrayValuesIfItHasAChain(mixed $input, Message $expectedMessage): void
    {
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $fieldName = 'field to process';
        $fieldset = new FieldSet($fieldName, new Field(''));

        $result = $fieldset->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $result);
    }

    #[DataProvider('dataSetsWithIncorrectValues')]
    #[Test]
    public function onlyAcceptsArrayValuesIfItHasADefault(mixed $input, Message $expectedMessage): void
    {
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $fieldName = 'field to process';
        $fieldset = new FieldSet($fieldName, DefaultProcessor::fromFiltersAndValidators());

        $result = $fieldset->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $result);
    }

    #[DataProvider('dataSetsWithIncorrectValues')]
    #[Test]
    public function acceptsAnyValueWithoutAChainOrDefault(mixed $input): void
    {
        $expected = Result::noResult($input);
        $fieldName = 'field to process';
        $fieldset = new FieldSet($fieldName);

        $result = $fieldset->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $result);
    }


    #[Test]
    public function onlyAcceptsOneBeforeSet(): void
    {
        $beforeSet = new BeforeSet();
        self::expectExceptionObject(InvalidProcessorArguments::multipleBeforeSetsInFieldSet());

        new FieldSet('field to process', $beforeSet, $beforeSet);
    }

    #[Test]
    public function onlyAcceptsOneAfterSet(): void
    {
        $afterSet = new AfterSet();
        self::expectExceptionObject(InvalidProcessorArguments::multipleAfterSetsInFieldSet());

        new FieldSet('field to process', $afterSet, $afterSet);
    }

    #[Test]
    public function onlyAcceptsOneDefaultField(): void
    {
        $defaultField = DefaultProcessor::fromFiltersAndValidators();
        self::expectExceptionObject(InvalidProcessorArguments::multipleDefaultProcessorsInFieldSet());

        new FieldSet('field to process', $defaultField, $defaultField);
    }

    #[Test]
    public function processesTest(): void
    {
        $fieldName = 'field to process';
        $fieldset = new FieldSet($fieldName);

        $output = $fieldset->processes();

        self::assertEquals($fieldName, $output);
    }

    #[Test]
    public function processMethodCallsFieldProcessesMethod(): void
    {
        $input = ['a' => 1, 'b' => 2, 'c' => 3];
        $field = self::createMock(Field::class);
        $field->expects(self::once())
            ->method('processes');
        $fieldset = new FieldSet('field to process', $field);

        $fieldset->process(new FieldName('Parent field'), $input);
    }

    #[Test]
    public function processCallsBeforeSetProcessOnceAndProcessesNever(): void
    {
        $input = ['a' => 1, 'b' => 2, 'c' => 3];
        $beforeSet = self::createMock(BeforeSet::class);
        $beforeSet->expects(self::never())
            ->method('processes');
        $beforeSet->expects(self::once())
            ->method('process')
            ->willReturn(Result::invalid($input));

        $fieldset = new FieldSet('field to process', $beforeSet);

        $fieldset->process(new FieldName('Parent field'), $input);
    }

    #[Test]
    public function processCallsAfterSetProcessOnceAndProcessesNever(): void
    {
        $input = ['a' => 1, 'b' => 2, 'c' => 3];
        $afterSet = self::createMock(AfterSet::class);
        $afterSet->expects(self::never())
            ->method('processes');
        $afterSet->expects(self::once())
            ->method('process')
            ->willReturn(Result::invalid($input));

        $fieldset = new FieldSet('field to process', $afterSet);

        $fieldset->process(new FieldName('Parent field'), $input);
    }

    public static function dataSetsOfFields(): array
    {
        return [
            'No chain returns noResult' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2, 'c' => 3]),
            ],
            'Return valid result' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 1, 'b' => 2, 'c' => 3]),
                new Field('a', new Passes()),
            ],
            'Return noResult' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2, 'c' => 3]),
                new Field('b', new Indifferent()),
            ],
            'Return invalid result' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::invalid(
                    ['a' => 1, 'b' => 2, 'c' => 3],
                    new MessageSet(
                        new FieldName('c', 'parent field', 'field to process'),
                        new Message('I always fail', [])
                    )
                ),
                new Field('c', new Fails()),
            ],
            'Field only performs processes on defined processes field' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 1.0, 'b' => 2, 'c' => 3]),
                new Field('a', new ToFloat()),
            ],
            'DefaultProcessor only processes fields not processed by other Field Processors' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 1.0, 'b' => '2', 'c' => '3']),
                new Field('a', new ToFloat()),
                DefaultProcessor::fromFiltersAndValidators(new ToString()),
            ],
            'DefaultProcessor without other processors' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => '1', 'b' => '2', 'c' => '3']),
                DefaultProcessor::fromFiltersAndValidators(new ToString()),
            ],
            'Field processed values persist' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 1, 'b' => 2.0, 'c' => 3]),
                new Field('b', new ToFloat(), new IsFloat()),
            ],
            'Multiple Fields are accepted' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 1.0, 'b' => 2, 'c' => '3']),
                new Field('a', new ToFloat()),
                new Field('a', new IsFloat()),
                new Field('c', new ToString()),
            ],
            'BeforeSet processes before Field' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2, 'd' => 3.0]),
                new BeforeSet(new Rename('c', 'd')),
                new Field('d', new ToFloat()),
            ],
            'BeforeSet processes before AfterSet' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 1, 'b' => 2, 'd' => 3]),
                new BeforeSet(new Rename('c', 'd')),
                new AfterSet(new RequiredFields('d')),
            ],
            'AfterSet processes after Field' => [
                ['a' => 1.0, 'b' => 1, 'c' => 1],
                Result::valid(['a' => 1, 'b' => 1, 'c' => 1]),
                new Field('a', new ToInt()),
                new AfterSet(new Identical()),
            ],
            'BeforeSet then Field then AfterSet' => [
                ['a' => 1, 'b' => 1, 'c' => 1.0],
                Result::valid(['a' => 1, 'b' => 1, 'd' => 1]),
                new BeforeSet(new Rename('c', 'd')),
                new Field('d', new ToInt()),
                new AfterSet(new Identical()),
            ],
        ];
    }


    #[DataProvider('dataSetsOfFields')]
    #[Test]
    public function processTest(array $input, Result $expected, Processor ...$chain): void
    {
        $fieldset = new FieldSet('field to process', ...$chain);

        $actual = $fieldset->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $actual);
        self::assertSame($expected->value, $actual->value);
    }
}
