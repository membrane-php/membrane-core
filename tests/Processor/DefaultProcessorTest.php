<?php

declare(strict_types=1);

namespace Processor;

use Membrane\Filter;
use Membrane\Processor\DefaultProcessor;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;
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
 * @uses   \Membrane\Validator\Utility\Fails
 * @uses   \Membrane\Validator\Utility\Indifferent
 * @uses   \Membrane\Validator\Utility\Passes
 */
class DefaultProcessorTest extends TestCase
{
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
        $filter1To2 = self::createMock(Filter::class);
        $filter1To2->method('filter')
            ->with(1)
            ->willReturn(Result::noResult(2));

        $filter2To3 = self::createMock(Filter::class);
        $filter2To3->method('filter')
            ->with(2)
            ->willReturn(Result::noResult(3));

        $validate1 = self::createMock(Validator::class);
        $validate1->method('validate')
            ->with(1)
            ->willReturn(Result::valid(1));

        $validate2 = self::createMock(Validator::class);
        $validate2->method('validate')
            ->with(2)
            ->willReturn(Result::valid(2));

        $invalidate1 = self::createMock(Validator::class);
        $invalidate1->method('validate')
            ->with(1)
            ->willReturn(Result::invalid(1, new MessageSet(null, new Message('oh no!', []))));

        return [
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
                Result::noResult(2),
                DefaultProcessor::fromFiltersAndValidators($filter1To2),
                1,
            ],
            'checks that changes made to value persist and chain runs in correct order' => [
                Result::noResult(3),
                DefaultProcessor::fromFiltersAndValidators($filter1To2, $filter2To3),
                1,
            ],
            'checks that chain stops as soon as result is invalid' => [
                Result::invalid(1, new MessageSet(new FieldName('', 'parent field'), new Message('oh no!', []))),
                DefaultProcessor::fromFiltersAndValidators($invalidate1, $filter1To2),
                1,
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
    }
}
