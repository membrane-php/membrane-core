<?php

declare(strict_types=1);

namespace Filter\CreateObject;

use Membrane\Filter\CreateObject\WithNamedArguments;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\CreateObject\WithNamedArguments
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class WithNamedArgumentsTest extends TestCase
{
    public function dataSetsThatPass(): array
    {
        $classWithNamedArguments = new class (a: 'default', b: 'arguments') {
            public function __construct(public string $a, public string $b)
            {
            }
        };

        $classWithDefaultValue = new class () {
            public function __construct(public string $a = 'default')
            {
            }
        };

        return [
            [$classWithNamedArguments, ['a' => 'default', 'b' => 'arguments']],
            [$classWithNamedArguments, ['default', 'arguments']],
            [$classWithNamedArguments, ['default', 'arguments', 'additional argument']],
            [$classWithDefaultValue, []],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatPass
     */
    public function createsNewInstanceOfClassWithNamedArguments(object $class, array $input): void
    {
        $withNamedArgs = new WithNamedArguments(get_class($class));
        $expected = Result::noResult($class);

        $result = $withNamedArgs->filter($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsThatFail(): array
    {
        $classWithNamedArguments = new class (a: 'default', b: 'arguments') {
            public function __construct(public string $a, public string $b)
            {
            }
        };

        return [
            [
                $classWithNamedArguments,
                ['a' => 'default', 'arguments'],
                'Cannot use positional argument after named argument during unpacking',
            ],
            [
                $classWithNamedArguments,
                ['a' => 'default', 'arguments', 'additional argument'],
                'Cannot use positional argument after named argument during unpacking',
            ],
            [
                $classWithNamedArguments,
                ['a' => 'default', 'b' => 'arguments', 'c' => 'additional argument'],
                'Unknown named parameter $c',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatFail
     */
    public function invalidParameterTest(object $class, array $input, string $expectedMessage): void
    {
        $withNamedArgs = new WithNamedArguments(get_class($class));
        $expected = Result::invalid($input, new MessageSet(null, new Message($expectedMessage, [])));

        $result = $withNamedArgs->filter($input);

        self::assertEquals($expected, $result);
    }
}
