<?php

declare(strict_types=1);

namespace Membrane\Tests\Processor;

use Membrane\Exception\InvalidProcessorArguments;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Processor\OneOf;
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

#[CoversClass(OneOf::class)]
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
class OneOfTest extends TestCase
{
    public static function dataSetsToConvertToPHPString(): array
    {
        return [
            '2 validators' => [new OneOf('a', new Field('b'), new Field('c'))],
            '3 validators' => [
                new OneOf('a', new Field('b', new Passes()), new Field('c', new Fails()), new Field('d')),
            ],
        ];
    }

    #[DataProvider('dataSetsToConvertToPHPString')]
    #[Test]
    public function toPHPTest(OneOf $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    #[Test]
    public function toStringTest(): void
    {
        $expected = <<<END
            One of the following:
            \t"id":
            \t\t- condition.
            \t"id":
            \t\t- condition.
            END;
        $processor = self::createMock(Processor::class);
        $processor->method('__toString')
            ->willReturn("\"id\":\n\t- condition");
        $sut = new OneOf('id', $processor, $processor);

        $actual = (string)$sut;

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function throwsExceptionIfLessThanTwoProcessors(): void
    {
        self::expectExceptionObject(InvalidProcessorArguments::redundantProcessor(OneOf::class));

        new OneOf('');
    }

    #[Test]
    public function processesTest(): void
    {
        $processes = 'test';
        $sut = new OneOf($processes, new Field(''), new Field(''));

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
                Result::invalid(
                    5,
                    new MessageSet(
                        new FieldName(''),
                        new Message('one and only one schema must pass', [])
                    )
                ),
            ],
            'two Fields with invalid results' => [
                '',
                [new Field('', new Fails()), new Field('', new Fails())],
                new FieldName(''),
                5,
                Result::invalid(
                    5,
                    new MessageSet(
                        new FieldName(''),
                        new Message('one and only one schema must pass', [])
                    ),
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
                Result::invalid(
                    5,
                    new MessageSet(
                        new FieldName(''),
                        new Message('one and only one schema must pass', [])
                    )
                ),
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
                Result::invalid(
                    5,
                    new MessageSet(
                        new FieldName(''),
                        new Message('one and only one schema must pass', [])
                    )
                ),
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
                ['id' => 'Harley', 'name' => 'Ben'],
                Result::valid(['id' => 'Harley', 'name' => 'Ben']),
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
                [],
                Result::invalid(
                    [],
                    new MessageSet(
                        new FieldName(''),
                        new Message('one and only one schema must pass', [])
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
                ['id' => 'Blink', 'name' => 'Harley'],
                Result::valid(['id' => 'Blink', 'name' => 'Harley']),
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
                ['id' => 5, 'name' => 'Harley'],
                Result::invalid(
                    ['id' => 5, 'name' => 'Harley'],
                    new MessageSet(
                        new FieldName(''),
                        new Message('one and only one schema must pass', [])
                    )
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
        $sut = new OneOf($processes, ...$processors);

        $actual = $sut->process($fieldName, $value);

        self::assertEquals($expected, $actual);
    }
}
