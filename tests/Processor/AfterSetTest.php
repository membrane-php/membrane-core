<?php

declare(strict_types=1);

namespace Processor;

use Membrane\Filter\Type\ToFloat;
use Membrane\Processor\AfterSet;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsFloat;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Processor\AfterSet
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Result\FieldName
 * @uses   \Membrane\Filter\Type\ToFloat
 * @uses   \Membrane\Validator\Type\IsFloat
 * @uses   \Membrane\Validator\Utility\Fails
 * @uses   \Membrane\Validator\Utility\Indifferent
 * @uses   \Membrane\Validator\Utility\Passes
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class AfterSetTest extends TestCase
{
    public static function dataSetsToConvertToString(): array
    {
        return [
            'No chain returns empty string' => [
                '',
                new AfterSet(),
            ],
            'Single item in chain returns one bullet point' => [
                "\n\t- will return valid.",
                new AfterSet(new Passes()),
            ],
            'guaranteed noResult in chain is ignored' => [
                '',
                new AfterSet(new Indifferent()),
            ],
            'Three items in chain returns three bullet points' => [
                "\n\t- will return valid.\n\t- will return invalid.\n\t- will return valid.",
                new AfterSet(new Passes(), new Fails(), new Passes()),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToConvertToString
     */
    public function toStringTest(string $expected, AfterSet $sut): void
    {
        $actual = (string)$sut;

        self::assertSame($expected, $actual);
    }

    public static function dataSetsToConvertToPHPString(): array
    {
        return [
            'no chain' => [
                new AfterSet(),
            ],
            '1 validator' => [
                new AfterSet(new Passes()),
            ],
            '3 validators' => [
                new AfterSet(new Passes(), new Fails(), new Passes()),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToConvertToPHPString
     */
    public function toPHPTest(AfterSet $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    /** @test */
    public function processesMethodReturnsEmptyString(): void
    {
        $expected = '';
        $afterSet = new AfterSet();

        $result = $afterSet->processes();

        self::assertSame($expected, $result);
    }

    public static function dataSetsForFiltersOrValidators(): array
    {
        return [
            'No chain returns noResult' => [
                Result::noResult(1),
                new AfterSet(),
                1,
            ],
            'Can return valid' => [
                Result::valid(1),
                new AfterSet(new Passes()),
                1,
            ],
            'Can return invalid' => [
                Result::invalid(
                    1,
                    new MessageSet(new FieldName('', 'parent field'), new Message('I always fail', []))
                ),
                new AfterSet(new Fails()),
                1,
            ],
            'Can return noResult' => [
                Result::noResult(1),
                new AfterSet(new Indifferent()),
                1,
            ],
            'checks it keeps track of previous results' => [
                Result::valid(1),
                new AfterSet(new Passes(), new Indifferent(), new Indifferent()),
                1,

            ],
            'checks it can make changes to value' => [
                Result::noResult(5.0),
                new AfterSet(new ToFloat()),
                '5',
            ],
            'checks that changes made to value persist and chain runs in correct order' => [
                Result::valid(5.0),
                new AfterSet(new ToFloat(), new IsFloat()),
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
                new AfterSet(new IsFloat(), new ToFloat()),
                '5',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForFiltersOrValidators
     */
    public function processesCallsFilterOrValidateMethods(Result $expected, AfterSet $sut, mixed $input): void
    {
        $actual = $sut->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $actual);
        self::assertSame($expected->value, $actual->value);
    }
}
