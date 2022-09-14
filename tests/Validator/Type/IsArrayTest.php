<?php

declare(strict_types=1);

namespace Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsArray;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Type\IsArray
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class IsArrayTest extends TestCase
{

    public function DataSetsThatPass(): array
    {
        return [
            [['a' => 'arrays have', 'b' => 'string keys']],
            [[]],
        ];
    }

    /**
     * @test
     * @dataProvider DataSetsThatPass
     */
    public function ArrayReturnsValid($input): void
    {
        $isArray = new IsArray;
        $expected = Result::valid($input);

        $result = $isArray->validate($input);

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
    public function TypesThatAreNotArraysReturnInvalid($input, $expectedVar): void
    {
        $isArray = new IsArray;
        $expectedMessage = new Message('Value passed to IsArray validator is not an array, %s passed instead', [$expectedVar]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $isArray->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function ListsReturnInvalid(): void
    {
        $input = ['this', 'is', 'a', 'list'];
        $expectedMessage = new Message('Value passed to IsArray validator is a list, string keys required for an array', []);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $isArray = new IsArray;

        $result = $isArray->validate($input);

        self::assertEquals($expected, $result);
    }
}
