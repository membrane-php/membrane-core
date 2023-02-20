<?php

declare(strict_types=1);

namespace Processor;

use Membrane\Filter\Type\ToFloat;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Field;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsFloat;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BeforeSet::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
#[UsesClass(ToFloat::class)]
#[UsesClass(IsFloat::class)]
#[UsesClass(Fails::class)]
#[UsesClass(Indifferent::class)]
#[UsesClass(Passes::class)]
#[UsesClass(Field::class)]
#[UsesClass(FieldName::class)]
class BeforeSetTest extends TestCase
{
    public static function dataSetsToConvertToString(): array
    {
        return [
            'No chain returns empty string' => [
                '',
                new BeforeSet(),
            ],
            'Single item in chain returns one bullet point' => [
                "\n\t- will return valid.",
                new BeforeSet(new Passes()),
            ],
            'guaranteed noResult in chain is ignored' => [
                '',
                new BeforeSet(new Indifferent()),
            ],
            'Three items in chain returns three bullet points' => [
                "\n\t- will return valid.\n\t- will return invalid.\n\t- will return valid.",
                new BeforeSet(new Passes(), new Fails(), new Passes()),
            ],
        ];
    }

    #[DataProvider('dataSetsToConvertToString')]
    #[Test]
    public function toStringTest(string $expected, BeforeSet $sut): void
    {
        $actual = (string)$sut;

        self::assertSame($expected, $actual);
    }


    public static function dataSetsToConvertToPHPString(): array
    {
        return [
            'no chain' => [
                new BeforeSet(),
            ],
            '1 validator' => [
                new BeforeSet(new Passes()),
            ],
            '3 validators' => [
                new BeforeSet(new Passes(), new Fails(), new Passes()),
            ],
        ];
    }

    #[DataProvider('dataSetsToConvertToPHPString')]
    #[Test]
    public function toPHPTest(BeforeSet $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    #[Test]
    public function processesMethodReturnsEmptyString(): void
    {
        $expected = '';
        $beforeSet = new BeforeSet();

        $result = $beforeSet->processes();

        self::assertSame($expected, $result);
    }

    public static function dataSetsForFiltersOrValidators(): array
    {
        return [
            'no chain returns noResult' => [
                Result::noResult(1),
                new BeforeSet(),
                1,
            ],
            'can return valid' => [
                Result::valid(1),
                new BeforeSet(new Passes()),
                1,
            ],
            'can return invalid' => [
                Result::invalid(
                    1,
                    new MessageSet(new FieldName('', 'parent field'), new Message('I always fail', []))
                ),
                new BeforeSet(new Fails()),
                1,
            ],
            'can return noResult' => [
                Result::noResult(1),
                new BeforeSet(new Indifferent()),
                1,
            ],
            'checks it keeps track of previous results' => [
                Result::valid(1),
                new BeforeSet(new Passes(), new Indifferent(), new Indifferent()),
                1,

            ],
            'checks it can make changes to value' => [
                Result::noResult(5.0),
                new BeforeSet(new ToFloat()),
                '5',
            ],
            'checks that changes made to value persist and chain runs in correct order' => [
                Result::valid(5.0),
                new BeforeSet(new ToFloat(), new IsFloat()),
                '5',
            ],
            'checks that chain stops as soon as result is invalid' => [
                Result::invalid(
                    '5',
                    new MessageSet(
                        new FieldName('', 'parent field'),
                        new Message('IsFloat expects float value, %s passed instead', ['string'])
                    )
                ),
                new BeforeSet(new IsFloat(), new ToFloat()),
                '5',
            ],
        ];
    }

    #[DataProvider('dataSetsForFiltersOrValidators')]
    #[Test]
    public function processesCallsFilterOrValidateMethods(Result $expected, BeforeSet $sut, mixed $input): void
    {
        $actual = $sut->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $actual);
        self::assertSame($expected->value, $actual->value);
    }
}
