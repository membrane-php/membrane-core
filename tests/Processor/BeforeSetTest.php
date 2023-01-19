<?php

declare(strict_types=1);

namespace Processor;

use Membrane\Filter\Type\ToFloat;
use Membrane\Processor\BeforeSet;
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
 * @covers \Membrane\Processor\BeforeSet
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Filter\Type\ToFloat
 * @uses   \Membrane\Validator\Type\IsFloat
 * @uses   \Membrane\Validator\Utility\Fails
 * @uses   \Membrane\Validator\Utility\Indifferent
 * @uses   \Membrane\Validator\Utility\Passes
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Result\FieldName
 */
class BeforeSetTest extends TestCase
{
    /**  @test */
    public function processesMethodReturnsEmptyString(): void
    {
        $expected = '';
        $beforeSet = new BeforeSet();

        $result = $beforeSet->processes();

        self::assertSame($expected, $result);
    }

    public function dataSetsForFiltersOrValidators(): array
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

    /**
     * @test
     * @dataProvider dataSetsForFiltersOrValidators
     */
    public function processesCallsFilterOrValidateMethods(Result $expected, BeforeSet $sut, mixed $input): void
    {
        $actual = $sut->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $actual);
        self::assertSame($expected->value, $actual->value);
    }
}
