<?php

declare(strict_types=1);

namespace Processor;

use Membrane\Filter\Type\ToFloat;
use Membrane\Processor\Field;
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
 * @covers \Membrane\Processor\Field
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
class FieldTest extends TestCase
{
    public function dataSetsToConvertToString(): array
    {
        return [
            'No chain returns empty string' => [
                '',
                new Field('a'),
            ],
            'Single item in chain returns bulletpoint on own if processes empty string' => [
                "\n\t- will return valid.",
                new Field('', new Passes()),
            ],
            'Single item in chain returns one bullet point' => [
                "\"c\":\n\t- will return valid.",
                new Field('c', new Passes()),
            ],
            'guaranteed noResult in chain is ignored' => [
                '',
                new Field('d', new Indifferent()),
            ],
            'Three items in chain returns three bullet points' => [
                "\"e\":\n\t- will return valid.\n\t- will return invalid.\n\t- will return valid.",
                new Field('e', new Passes(), new Fails(), new Passes()),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToConvertToString
     */
    public function toStringTest(string $expected, Field $sut): void
    {
        $actual = (string)$sut;

        self::assertSame($expected, $actual);
    }

    /** @test */
    public function processesMethodReturnsProcessesString(): void
    {
        $input = 'FieldName to process';
        $field = new Field($input);

        $output = $field->processes();

        self::assertEquals($output, $input);
    }

    public function dataSetsForFiltersOrValidators(): array
    {
        return [
            'No chain returns noResult' => [
                Result::noResult(1),
                new Field('a'),
                1,
            ],
            'Can return valid' => [
                Result::valid(1),
                new Field('a', new Passes()),
                1,
            ],
            'Can return invalid' => [
                Result::invalid(
                    1,
                    new MessageSet(new FieldName('b', 'parent field'), new Message('I always fail', []))
                ),
                new Field('b', new Fails()),
                1,
            ],
            'Can return noResult' => [
                Result::noResult(1),
                new Field('c', new Indifferent()),
                1,
            ],
            'checks it keeps track of previous results' => [
                Result::valid(1),
                new Field('d', new Passes(), new Indifferent(), new Indifferent()),
                1,

            ],
            'checks it can make changes to value' => [
                Result::noResult(5.0),
                new Field('e', new ToFloat()),
                '5',
            ],
            'checks that changes made to value persist and chain runs in correct order' => [
                Result::valid(5.0),
                new Field('f', new ToFloat(), new IsFloat()),
                '5',
            ],
            'checks that chain stops as soon as result is invalid' => [
                Result::invalid(
                    '5',
                    new MessageSet(
                        new FieldName('g', 'parent field'),
                        new Message('IsFloat expects float value, %s passed instead', ['string'])
                    )
                ),
                new Field('g', new IsFloat(), new ToFloat()),
                '5',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForFiltersOrValidators
     */
    public function processesCallsFilterOrValidateMethods(Result $expected, Field $sut, mixed $input): void
    {
        $actual = $sut->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $actual);
        self::assertSame($expected->value, $actual->value);
    }
}
