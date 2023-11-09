<?php

declare(strict_types=1);

namespace Processor;

use Generator;
use Membrane\Filter;
use Membrane\Filter\Type\ToFloat;
use Membrane\OpenAPI\Processor\AllOf;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Processor\OneOf;
use Membrane\Processor;
use Membrane\Processor\DefaultProcessor;
use Membrane\Processor\Field;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DefaultProcessor::class)]
#[UsesClass(Field::class)]
#[UsesClass(FieldName::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
#[UsesClass(ToFloat::class)]
#[UsesClass(Validator\Type\IsFloat::class)]
#[UsesClass(Validator\Type\IsInt::class)]
#[UsesClass(Validator\Type\IsBool::class)]
#[UsesClass(Fails::class)]
#[UsesClass(Indifferent::class)]
#[UsesClass(Passes::class)]
#[UsesClass(AllOf::class)]
#[UsesClass(AnyOf::class)]
#[UsesClass(OneOf::class)]
class DefaultProcessorTest extends TestCase
{
    public static function provideFiltersAndValidators(): Generator
    {
        yield 'nothing' => [];
        yield '1 fails' => [new Fails()];
        yield '1 indifferent' => [new Indifferent()];
        yield '1 passes' => [new Passes()];
        yield '3 fails' => [new Fails(), new Fails(), new Fails()];
        yield '3 indifferents' => [new Indifferent(), new Indifferent(), new Indifferent()];
        yield '3 passes' => [new Passes(), new Passes(), new Passes()];
        yield '1 fails, 1 indifferent, 1 passes' => [new Fails(), new Indifferent(), new Passes()];
        yield '1 passes, 2 indifferent' => [new Passes(), new Indifferent(), new Indifferent()];
        yield '1 toFloat' => [new ToFloat()];
        yield '1 isFloat, 1 toFloat' => [new Validator\Type\IsFloat(), new ToFloat()];
        yield '1 toFloat, 1 isFloat' => [new ToFloat(), new Validator\Type\IsFloat()];
    }

    #[Test, DataProvider('provideFiltersAndValidators')]
    public function itCanBeStaticallyConstructed(Filter | Validator ...$chain): void
    {
        $expected = new DefaultProcessor(new Field('', ...$chain));

        $actual = DefaultProcessor::fromFiltersAndValidators(...$chain);

        self::assertEquals($expected, $actual);
    }

    #[Test, DataProvider('provideFiltersAndValidators')]
    public function toStringTest(Filter | Validator ...$chain): void
    {
        $expected = '';
        foreach ($chain as $item) {
            if ((string)$item !== '') {
                $expected .= sprintf("\n\t- %s.", $item);
            }
        }

        $sut = DefaultProcessor::fromFiltersAndValidators(...$chain);

        self::assertSame($expected, (string)$sut);
    }

    #[Test, DataProvider('provideFiltersAndValidators')]
    public function toPHPTest(Filter | Validator ...$chain): void
    {
        $sut = DefaultProcessor::fromFiltersAndValidators(...$chain);

        $actual = sprintf('return %s;', $sut->__toPHP());

        self::assertEquals($sut, eval($actual));
    }

    #[Test]
    public function processesTest(): void
    {
        $sut = new DefaultProcessor(new Field('This wont show up'));

        self::assertSame('', $sut->processes());
    }

    public static function provideProcessors(): Generator
    {
        foreach (self::provideFiltersAndValidators() as $case => $chain) {
            yield sprintf('input of 5 and a field with %s', $case) => [5, new Field('', ...$chain)];
        }

        $isBool = new Field('', new Validator\Type\IsBool());
        $isInt = new Field('', new Validator\Type\IsInt());
        $isNumber = new Field('', new Validator\Type\IsNumber());

        foreach ([true, 5, 5.5,] as $input) {
            yield sprintf('input of %s and a oneOf: bool|int', $input) => [$input, new OneOf('', $isBool, $isInt)];
            yield sprintf('input of %s and an anyOf: bool|int', $input) => [$input, new AnyOf('', $isBool, $isInt)];
            yield sprintf('input of %s and an allOf: bool|int', $input) => [$input, new AllOf('', $isBool, $isInt)];
            yield sprintf('input of %s and a oneOf: int|number', $input) => [$input, new OneOf('', $isInt, $isNumber)];
            yield sprintf('input of %s and an anyOf: int|number', $input) => [$input, new AnyOf('', $isInt, $isNumber)];
            yield sprintf('input of %s and an allOf: int|number', $input) => [$input, new AllOf('', $isInt, $isNumber)];
        }
    }

    #[Test, DataProvider('provideProcessors')]
    public function itUsesTheWrappedProcessorToProcess(mixed $input, Processor $processor): void
    {
        $expected = $processor->process(new FieldName(''), $input);

        $actual = (new DefaultProcessor($processor))->process(new FieldName(''), $input);

        self::assertEquals($expected, $actual);
        self::assertSame($expected->value, $actual->value);
    }
}
