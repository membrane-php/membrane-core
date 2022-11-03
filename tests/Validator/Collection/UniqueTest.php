<?php

declare(strict_types=1);

namespace Validator\Collection;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Collection\Unique;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Collection\Unique
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class UniqueTest extends TestCase
{
    public function dataSetsWithIncorrectTypes(): array
    {
        return [
            [123, 'integer'],
            [1.23, 'double'],
            ['string', 'string'],
            [true, 'boolean'],
            [null, 'NULL'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithIncorrectTypes
     */
    public function incorrectTypesReturnInvalidResults($input, $expectedVars): void
    {
        $unique = new Unique();
        $expected = Result::invalid(
            $input,
            new MessageSet(
                null,
                new Message('Unique Validator requires an array, %s given', [$expectedVars])
            )
        );

        $result = $unique->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsToValidate(): array
    {
        $invalidMessageSet = new MessageSet(null, new Message('Collection contains duplicate values', []));
        return [
            'some items are duplicates' => [
                ['a', 'b', 'a'],
                Result::invalid(['a', 'b', 'a'], $invalidMessageSet),
            ],
            'every item is a duplicate' => [
                ['a', 'a', 'a'],
                Result::invalid(['a', 'a', 'a'], $invalidMessageSet),
            ],
            'no items' => [
                [],
                Result::valid([]),
            ],
            'one item' => [
                ['a'],
                Result::valid(['a']),
            ],
            'every item is unique' => [
                ['a', 'b', 'c'],
                Result::valid(['a', 'b', 'c']),
            ],
            'items are equal, but not identical' => [
                ['1', 1, 1.0],
                Result::valid(['1', 1, 1.0]),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToValidate
     */
    public function validateTest(array $value, Result $expected): void
    {
        $sut = new Unique();

        $actual = $sut->validate($value);

        self::assertEquals($expected, $actual);
    }

}
