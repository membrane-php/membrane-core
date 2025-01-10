<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Processor;

use Membrane\Exception\InvalidProcessorArguments;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Type\IsArray;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsString;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AnyOf::class)]
#[CoversClass(InvalidProcessorArguments::class)]
#[UsesClass(BeforeSet::class)]
#[UsesClass(Field::class)]
#[UsesClass(FieldSet::class)]
#[UsesClass(FieldName::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
#[UsesClass(RequiredFields::class)]
#[UsesClass(IsArray::class)]
#[UsesClass(IsInt::class)]
#[UsesClass(IsString::class)]
#[UsesClass(Fails::class)]
#[UsesClass(Indifferent::class)]
#[UsesClass(Passes::class)]
class AnyOfTest extends TestCase
{
    public static function dataSetsToConvertToPHPString(): array
    {
        return [
            '2 validators' => [new AnyOf('a', new Field('b'), new Field('c'))],
            '3 validators' => [
                new AnyOf('a', new Field('b', new Passes()), new Field('c', new Fails()), new Field('d')),
            ],
        ];
    }

    #[DataProvider('dataSetsToConvertToPHPString')]
    #[Test]
    public function toPHPTest(AnyOf $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    #[Test]
    public function toStringTest(): void
    {
        $expected = <<<END
            Any of the following:
            \t"id":
            \t\t- condition.
            \t"id":
            \t\t- condition.
            END;
        $processor = $this->createMock(Processor::class);
        $processor->method('__toString')
            ->willReturn("\"id\":\n\t- condition");
        $sut = new AnyOf('id', $processor, $processor);

        $actual = (string)$sut;

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function throwsExceptionIfLessThanTwoProcessors(): void
    {
        self::expectExceptionObject(InvalidProcessorArguments::redundantProcessor(AnyOf::class));

        new AnyOf('');
    }

    #[Test]
    public function processesTest(): void
    {
        $processes = 'test';
        $sut = new AnyOf($processes, new FieldSet(''), new FieldSet(''));

        self::assertEquals($processes, $sut->processes());
    }

    public static function dataSetsToProcess(): array
    {
        return [
            'two Fields with valid results' => [
                '',
                [new Field('', new Passes()), new Field('', new Passes())],
                new FieldName(''),
                5,
                Result::valid(5),
            ],
            'two Fields with invalid results' => [
                '',
                [new Field('', new Fails()), new Field('', new Fails())],
                new FieldName(''),
                5,
                Result::invalid(
                    5,
                    new MessageSet(
                        new FieldName('', ''),
                        new Message('I always fail', [])
                    ),
                    new MessageSet(
                        new FieldName('', ''),
                        new Message('I always fail', [])
                    ),
                ),
            ],
            'two Fields with no results' => [
                '',
                [new Field('', new Indifferent()), new Field('', new Indifferent())],
                new FieldName(''),
                5,
                Result::noResult(5),
            ],
            'one valid result, one invalid result' => [
                '',
                [new Field('', new Fails()), new Field('', new Passes())],
                new FieldName(''),
                5,
                Result::valid(5),
            ],
            'one valid result, one no result' => [
                '',
                [new Field('', new Indifferent()), new Field('', new Passes())],
                new FieldName(''),
                5,
                Result::valid(5),
            ],
            'expects an object which may have integer id and string name (valid input)' => [
                '',
                [
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray())
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray())
                    ),
                ],
                new FieldName(''),
                ['id' => 5, 'name' => 5],
                Result::valid(['id' => 5, 'name' => 5]),
            ],
            'expects an object which may have integer id and string name (invalid input)' => [
                '',
                [
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray())
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray())
                    ),
                ],
                new FieldName(''),
                ['id' => 'Ben', 'name' => 5],
                Result::invalid(
                    ['id' => 'Ben', 'name' => 5],
                    new MessageSet(
                        new FieldName('id', '', ''),
                        new Message('IsInt validator expects integer value, %s passed instead', ['string'])
                    ),
                    new MessageSet(
                        new FieldName('name', '', ''),
                        new Message('IsString validator expects string value, %s passed instead', ['integer'])
                    )
                ),
            ],
            'expects an object which must have integer id and string name (valid input)' => [
                '',
                [
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    ),
                ],
                new FieldName(''),
                ['name' => 'Harley'],
                Result::valid(['name' => 'Harley']),
            ],
            'expects an object which must have integer id and string name (invalid input)' => [
                '',
                [
                    new FieldSet(
                        '',
                        new Field('id', new IsInt()),
                        new BeforeSet(new IsArray(), new RequiredFields('id'))
                    ),
                    new FieldSet(
                        '',
                        new Field('name', new IsString()),
                        new BeforeSet(new IsArray(), new RequiredFields('name'))
                    ),
                ],
                new FieldName(''),
                ['id' => 'Blink'],
                Result::invalid(
                    ['id' => 'Blink'],
                    new MessageSet(
                        new FieldName('id', '', ''),
                        new Message('IsInt validator expects integer value, %s passed instead', ['string'])
                    ),
                    new MessageSet(new FieldName('', '', ''), new Message('%s is a required field', ['name']))
                ),
            ],
        ];
    }

    #[DataProvider('dataSetsToProcess')]
    #[Test]
    public function processTest(
        string $processes,
        array $processors,
        FieldName $fieldName,
        mixed $value,
        Result $expected
    ): void {
        $sut = new AnyOf($processes, ...$processors);

        $actual = $sut->process($fieldName, $value);

        self::assertEquals($expected, $actual);
    }
}
