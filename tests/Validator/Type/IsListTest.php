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
    public function dataSetsThatPass(): array
    {
        return [
            [['this', 'is', 'a', 'list']],
            [[]],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatPass
     */
    public function listReturnsValid($input): void
    {
        $isList = new IsList();
        $expected = Result::valid($input);

        $result = $isList->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsThatAreNotArraysOrLists(): array
    {
        return [
            ['true', 'string'],
            [1, 'integer'],
            [1.1, 'double'],
            [false, 'boolean'],
            [null, 'NULL'],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatAreNotArraysOrLists
     */
    public function typesThatAreNotArraysOrListsReturnInvalid($input, $expectedVar): void
    {
        $isList = new IsList();
        $expectedMessage = new Message(
            'IsList validator expects list value, %s passed instead',
            [$expectedVar]
        );
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $isList->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function arrayReturnsInvalid(): void
    {
        $input = ['a' => 'this is', 'b' => 'an array'];
        $expectedMessage = new Message(
            'IsList validator expects list value, lists do not have keys',
            []
        );
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $isList = new IsList();

        $result = $isList->validate($input);

        self::assertEquals($expected, $result);
    }
}
