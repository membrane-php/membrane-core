<?php
declare(strict_types=1);

namespace Filter\CreateObject;

use Membrane\Filter\CreateObject\WithNamedArguments;
use Membrane\Result\Message;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\CreateObject\WithNamedArguments
 * @uses \Membrane\Result\Result
 * @uses \Membrane\Result\MessageSet
 * @uses \Membrane\Result\Message
 */
class WithNamedArgumentsTest extends TestCase
{

    public function dataSetsThatPass() : array
    {
        $classWithNamedArguments = new class (a: 'default' , b: 'arguments')
        {
            function __construct (public string $a, public string $b) {}
        };

        $classWithDefaultValue = new class ()
        {
            function __construct (public string $a = 'default') {}
        };

        return [
            [$classWithNamedArguments, ['a' => 'default', 'b' => 'arguments']],
            [$classWithNamedArguments, ['default', 'arguments']],
            [$classWithNamedArguments, ['default', 'arguments', 'additional argument']],
            [$classWithDefaultValue, []]
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatPass
     */
    public function CreatesNewInstanceOfClassWithNamedArguments($class, $input) : void
    {
        $sut = new WithNamedArguments(get_class($class));
        $expectedResult = Result::NO_RESULT;

        $result = $sut->filter($input);

        self::assertEquals($class, $result->value);
        self::assertEquals($expectedResult, $result->result);
    }

    public function dataSetsThatFail() : array
    {
        $classWithNamedArguments = new class (a: 'default' , b: 'arguments')
        {
            function __construct (public string $a, public string $b) {}
        };

        return [
            [
                $classWithNamedArguments,
                ['a' => 'default', 'arguments'],
                new Message('test', [])
            ],
            [
                $classWithNamedArguments,
                ['a' => 'default', 'arguments', 'additional argument'],
                new Message('hi', [])
            ],
            [
                $classWithNamedArguments,
                ['a' => 'default', 'b' => 'arguments', 'c' => 'additional argument'],
                new Message('Unknown named parameter $c', [])
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatFail
     */
    public function InvalidParameterTest($class, $input, $expectedMessage) : void
    {
        $sut = new WithNamedArguments(get_class($class));
        $expectedResult = Result::INVALID;

        $result = $sut->filter($input);
        self::assertEquals($expectedResult, $result->result);
        self::assertEquals($input, $result->value);
//        self::assertEquals($expectedMessage, $result->messageSets[0]?->messages[0]?->message);
    }
}