<?php

declare(strict_types=1);

namespace Processor;

use Membrane\Filter;
use Membrane\Processor\AfterSet;
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
 * @covers \Membrane\Processor\AfterSet
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Result\FieldName
 * @uses   \Membrane\Validator\Utility\Fails
 * @uses   \Membrane\Validator\Utility\Indifferent
 * @uses   \Membrane\Validator\Utility\Passes
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class AfterSetTest extends TestCase
{
    /**
     * @test
     */
    public function processesMethodReturnsEmptyString(): void
    {
        $expected = '';
        $afterSet = new AfterSet();

        $result = $afterSet->processes();

        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function noChainReturnsNoResult(): void
    {
        $input = ['a' => 1, 'b' => 2, 'c' => 3];
        $expected = Result::noResult($input);
        $field = new AfterSet();

        $result = $field->process(new FieldName('Parent FieldName'), $input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsForFiltersOrValidators(): array
    {
        $incrementFilter = new class implements Filter {
            public function filter(mixed $value): Result
            {
                foreach (array_keys($value) as $key) {
                    $value[$key]++;
                }

                return Result::noResult($value);
            }
        };

        $evenFilter = new class implements Filter {
            public function filter(mixed $value): Result
            {
                foreach (array_keys($value) as $key) {
                    $value[$key] *= 2;
                }

                return Result::noResult($value);
            }
        };

        $evenValidator = new class implements Validator {
            public function validate(mixed $value): Result
            {
                foreach (array_keys($value) as $key) {
                    if ($value[$key] % 2 !== 0) {
                        return Result::invalid($value, new MessageSet(
                            null,
                            new Message('not even', [])
                        ));
                    }
                }
                return Result::valid($value);
            }
        };

        return [
            'checks it can return valid' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 1, 'b' => 2, 'c' => 3]),
                new Passes(),
            ],
            'checks it can return invalid' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::invalid(['a' => 1, 'b' => 2, 'c' => 3], new MessageSet(
                    new FieldName('', 'parent field'),
                    new Message('I always fail', [])
                )),
                new Fails(),
            ],
            'checks it can return noresult' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 1, 'b' => 2, 'c' => 3]),
                new Indifferent(),
            ],
            'checks it keeps track of previous results' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::valid(['a' => 1, 'b' => 2, 'c' => 3]),
                new Passes(),
                new Indifferent(),
                new Indifferent(),
            ],
            'checks it can make changes to value' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 2, 'b' => 3, 'c' => 4]),
                $incrementFilter,
            ],
            'checks that changes made to value persist' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::noResult(['a' => 3, 'b' => 4, 'c' => 5]),
                $incrementFilter,
                $incrementFilter,
            ],
            'checks that chain runs in correct order' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::invalid(['a' => 1, 'b' => 2, 'c' => 3], new MessageSet(
                    new FieldName('', 'parent field'),
                    new Message('not even', [])
                )),
                $evenValidator,
                $evenFilter,
            ],
            'checks that chain stops as soon as result is invalid' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                Result::invalid(['a' => 2, 'b' => 3, 'c' => 4], new MessageSet(
                    new FieldName('', 'parent field'),
                    new Message('not even', [])
                )),
                $incrementFilter,
                $evenValidator,
                $incrementFilter,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForFiltersOrValidators
     */
    public function processesCallsFilterOrValidatorMethods(
        mixed $input,
        Result $expected,
        Filter|Validator ...$chain
    ): void {
        $afterSet = new AfterSet(...$chain);

        $output = $afterSet->process(new FieldName('parent field'), $input);

        self::assertEquals($expected, $output);
    }
}
