<?php

declare(strict_types=1);

namespace Processor;

use Membrane\Filter\Type\ToFloat;
use Membrane\Processor\DefaultProcessor;
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
 * @covers \Membrane\Processor\DefaultProcessor
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Result\FieldName
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Filter\Type\ToFloat
 * @uses   \Membrane\Validator\Type\IsFloat
 * @uses   \Membrane\Validator\Utility\Fails
 * @uses   \Membrane\Validator\Utility\Indifferent
 * @uses   \Membrane\Validator\Utility\Passes
 */
class DefaultProcessorTest extends TestCase
{
    public function dataSetsToConvertToString(): array
    {
        return [
            'No chain returns empty string' => [
                '',
                DefaultProcessor::fromFiltersAndValidators(),
            ],
            'Single item in chain returns one bullet point' => [
                "\n\t- will return valid.",
                DefaultProcessor::fromFiltersAndValidators(new Passes()),
            ],
            'guaranteed noResult in chain is ignored' => [
                '',
                DefaultProcessor::fromFiltersAndValidators(new Indifferent()),
            ],
            'Three items in chain returns three bullet points' => [
                "\n\t- will return valid.\n\t- will return invalid.\n\t- will return valid.",
                DefaultProcessor::fromFiltersAndValidators(new Passes(), new Fails(), new Passes()),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToConvertToString
     */
    public function toStringTest(string $expected, DefaultProcessor $sut): void
    {
        $actual = (string)$sut;

        self::assertSame($expected, $actual);
    }

    /** @test */
    public function processesTest(): void
    {
        $expected = '';
        $sut = DefaultProcessor::fromFiltersAndValidators();

        $actual = $sut->processes();

        self::assertSame($expected, $actual);
    }

    public function dataSetsForFiltersOrValidators(): array
    {
        return [
            'no chain returns noResult' => [
                Result::noResult(1),
                DefaultProcessor::fromFiltersAndValidators(),
                1,
            ],
            'checks it can return valid' => [
                Result::valid(1),
                DefaultProcessor::fromFiltersAndValidators(new Passes()),
                1,
            ],
            'checks it can return invalid' => [
                Result::invalid(
                    1,
                    new MessageSet(new FieldName('', 'parent field'), new Message('I always fail', []))
                ),
                DefaultProcessor::fromFiltersAndValidators(new Fails()),
                1,
            ],
            'checks it can return noResult' => [
                Result::noResult(1),
                DefaultProcessor::fromFiltersAndValidators(new Indifferent()),
                1,
            ],
            'checks it keeps track of previous results' => [
                Result::valid(1),
                DefaultProcessor::fromFiltersAndValidators(new Passes(), new Indifferent(), new Indifferent()),
                1,

            ],
            'checks it can make changes to value' => [
                Result::noResult(5.0),
                DefaultProcessor::fromFiltersAndValidators(new ToFloat()),
                '5',
            ],
            'checks that changes made to value persist and chain runs in correct order' => [
                Result::valid(5.0),
                DefaultProcessor::fromFiltersAndValidators(new ToFloat(), new IsFloat()),
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
                DefaultProcessor::fromFiltersAndValidators(new IsFloat(), new ToFloat()),
                '5',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForFiltersOrValidators
     */
    public function processesCallsFilterOrValidateMethods(Result $expected, DefaultProcessor $sut, mixed $input): void
    {
        $actual = $sut->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $actual);
        self::assertSame($expected->value, $actual->value);
    }
}
