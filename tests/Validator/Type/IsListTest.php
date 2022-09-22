<?php

declare(strict_types=1);

namespace Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsList;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Type\IsList
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class IsListTest extends TestCase
{
    public function DataSetsThatPass(): array
    {
        return [
            [['this', 'is', 'a', 'list']],
            [[]],
        ];
    }

    /**
     * @test
     * @dataProvider DataSetsThatPass
     */
    public function ListReturnsValid($input): void
    {
        $isList = new IsList();
        $expected = Result::valid($input);

        $result = $isList->validate($input);

        self::assertEquals($expected, $result);
    }

    public function DataSetsThatAreNotArraysOrLists(): array
    {
        return [
            ['true', 'string'],
            [1, 'integer'],
            [1.1, 'double'],
            [false, 'boolean'],
            [null, 'NULL']
        ];
    }

    /**
     * @test
     * @dataProvider DataSetsThatAreNotArraysOrLists
     */
    public function TypesThatAreNotArraysOrListsReturnInvalid($input, $expectedVar): void
    {
        $isList = new IsList();
        $expectedMessage = new Message('Value passed to IsList validator is not an array, %s passed instead', [$expectedVar]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $isList->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function ArrayReturnsInvalid(): void
    {
        $input = ['a' => 'this is', 'b' => 'an array'];
        $expectedMessage = new Message('Value passed to IsList validator is an array, lists do not have keys', []);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $isList = new IsList();

        $result = $isList->validate($input);

        self::assertEquals($expected, $result);
    }
}
