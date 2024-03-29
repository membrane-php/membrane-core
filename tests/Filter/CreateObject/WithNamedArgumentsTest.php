<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\CreateObject;

use Membrane\Filter\CreateObject\WithNamedArguments;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WithNamedArguments::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class WithNamedArgumentsTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'construct an instance of "\a\b" from named arguments contained in self';
        $sut = new WithNamedArguments('\a\b');

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new WithNamedArguments('Arbitrary\Class');

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsThatPass(): array
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

    #[DataProvider('dataSetsThatPass')]
    #[Test]
    public function createsNewInstanceOfClassWithNamedArguments(object $class, array $input): void
    {
        $withNamedArgs = new WithNamedArguments(get_class($class));
        $expected = Result::noResult($class);

        $result = $withNamedArgs->filter($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsThatFail(): array
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

    #[DataProvider('dataSetsThatFail')]
    #[Test]
    public function invalidParameterTest(object $class, array $input, string $expectedMessage): void
    {
        $withNamedArgs = new WithNamedArguments(get_class($class));
        $expected = Result::invalid($input, new MessageSet(null, new Message($expectedMessage, [])));

        $result = $withNamedArgs->filter($input);

        self::assertEquals($expected, $result);
    }
}
