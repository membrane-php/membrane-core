<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\Type;

use Generator;
use IntBackedEnum;
use Membrane\Filter\Type\ToBackedEnum;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Tests\Fixtures\Enum\IntBackedDummy;
use Membrane\Tests\Fixtures\Enum\StringBackedDummy;
use Membrane\Tests\MembraneTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ToBackedEnum::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class ToBackedEnumTest extends MembraneTestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = sprintf('convert to %s', IntBackedDummy::class);

        $sut = new ToBackedEnum(IntBackedDummy::class);

        self::assertSame($expected, $sut->__toString());
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new ToBackedEnum(StringBackedDummy::class);

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    /**
     * @param class-string $backedEnumName
     */
    #[Test]
    #[DataProvider('provideValuesToFilter')]
    public function itFiltersValuesToBackedEnums(
        string $backedEnumName,
        mixed $value,
        Result $expected,
    ): void {
        $toBool = new ToBackedEnum($backedEnumName);

        $actual = $toBool->filter($value);

        self::assertResultEquals($expected, $actual);
    }

    /** @return Generator<array{0:class-string, 1:mixed, 2:Result}> */
    public static function provideValuesToFilter(): Generator
    {
        yield 'int value that matches a case' => [
            IntBackedDummy::class,
            1,
            Result::noResult(IntBackedDummy::One),
        ];
        yield 'string value that matches a case' => [
            StringBackedDummy::class,
            'hello',
            Result::noResult(StringBackedDummy::Hello),
        ];

        yield 'int value that does not match a case' => [
            IntBackedDummy::class,
            404,
            Result::invalid(404, new MessageSet(null, new Message(
                'value does not match a case of %s',
                [IntBackedDummy::class]
            ))),
        ];
        yield 'string value that does not match a case' => [
            StringBackedDummy::class,
            'goodbye',
            Result::invalid('goodbye', new MessageSet(null, new Message(
                'value does not match a case of %s',
                [StringBackedDummy::class]
            ))),
        ];

        yield 'int value to string-backed enum' => [
            StringBackedDummy::class,
            0,
            Result::invalid(0, new MessageSet(null, new Message(
                '%s value does not match backing type of %s',
                ['integer', StringBackedDummy::class]
            ))),
        ];
        yield 'string value to int-backed enum' => [
            IntBackedDummy::class,
            'hello',
            Result::invalid('hello', new MessageSet(null, new Message(
                '%s value does not match backing type of %s',
                ['string', IntBackedDummy::class]
            ))),
        ];
    }
}
