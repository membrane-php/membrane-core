<?php

declare(strict_types=1);

namespace Filter\CreateObject;

use Membrane\Exception\InvalidFilterArguments;
use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Tests\MembraneTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
#[CoversClass(Filter\CreateObject\CallMethod::class)]
class CallMethodTest extends MembraneTestCase
{
    #[Test]
    public function itExpectsClassToExist(): void
    {
        $class = 'n/a';
        $method = 'n/a';

        self::expectExceptionObject(
            InvalidFilterArguments::methodNotCallable($class, $method),
        );

        new Filter\CreateObject\CallMethod($class, $method);
    }

    #[Test]
    public function itExpectsMethodToExist(): void
    {
        $class = new class () {};
        $method = 'n/a';

        self::expectExceptionObject(
            InvalidFilterArguments::methodNotCallable($class::class, $method),
        );

        new Filter\CreateObject\CallMethod($class::class, $method);
    }

    #[Test]
    public function itExpectsMethodToBePublic(): void
    {
        $class = new class() {private static function foo() {}};
        $method = 'foo';

        self::expectExceptionObject(
            InvalidFilterArguments::methodNotCallable($class::class, $method),
        );

        new Filter\CreateObject\CallMethod($class::class, $method);
    }

    #[Test]
    public function itExpectsMethodToBeStatic(): void
    {
        $class = new class() {public function foo() {}};
        $method = 'foo';

        self::expectExceptionObject(
            InvalidFilterArguments::methodNotCallable($class::class, $method),
        );

        new Filter\CreateObject\CallMethod($class::class, $method);
    }

    #[Test]
    public function itIsStringable(): void
    {
        $class = new class() {public static function foo() {}};
        $method = 'foo';

        $sut = new Filter\CreateObject\CallMethod($class::class, $method);

        self::assertSame(
            sprintf('Call %s::%s with array value as arguments', $class::class, $method),
            $sut->__toString(),
        );
    }

    #[Test]
    public function itIsPhpStringable(): void
    {
        $class = new class() {public static function foo() {}};
        $method = 'foo';

        $sut = new Filter\CreateObject\CallMethod($class::class, $method);

        self::assertSame(
            sprintf(
                'new %s(\'%s\', \'%s\')',
                $sut::class,
                $class::class,
                $method,
            ),
            $sut->__toPHP(),
        );
    }

    #[Test]
    #[DataProvider('provideValuesToFilter')]
    public function itFiltersValue(
        Result $expected,
        string $class,
        string $method,
        mixed $value,
    ): void {
        $sut = new Filter\CreateObject\CallMethod($class, $method);

        self::assertResultEquals($expected, $sut->filter($value));
    }

    /**
     * @return \Generator<array{
     *     0: Result,
     *     1: class-string,
     *     2: string,
     *     3: mixed,
     * }>
     */
    public static function provideValuesToFilter(): \Generator
    {
        yield 'it expects an array' => (function () {
            $class = new class () {public static function foo() {}};
            return [
                Result::invalid(
                    'Howdy, planet!',
                    new MessageSet(
                        null,
                        new Message('CallMethod requires arrays of arguments, %s given', ['string']),
                    )
                ),
                $class::class,
                'foo',
                'Howdy, planet!',
            ];
        })();

        yield 'noop' => (function () {
            $class = new class () {public static function foo() {}};
            return [
                Result::noResult(null),
                $class::class,
                'foo',
                [],
            ];
        })();

        yield 'returns 1' => (function () {
            $class = new class () {public static function foo() {return 1;}};
            return [
                Result::noResult(1),
                $class::class,
                'foo',
                ['Hello, world!'],
            ];
        })();

        yield 'capitalizes string' => (function () {
            $class = new class () {
                public static function shout(string $greeting) {
                    return strtoupper($greeting);
                }
            };
            return [
                Result::noResult('HOWDY, PLANET!'),
                $class::class,
                'shout',
                ['Howdy, planet!'],
            ];
        })();

        yield 'sum numbers' => (function () {
            $class = new class () {
                public static function sum(...$numbers) {
                    return array_sum($numbers);
                }
            };
            return [
                Result::noResult(6),
                $class::class,
                'sum',
                [1, 2, 3],
            ];
        })();
    }
}
