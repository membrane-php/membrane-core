<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\Type;

use Generator;
use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Tests\Fixtures\Filter\ToThis;
use Membrane\Tests\MembraneTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(Filter\Type\NullOr::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class NullOrTest extends MembraneTestCase
{
    #[Test]
    public function itFiltersNull(): void
    {
        $sut = new Filter\Type\NullOr(new ToThis());

        self::assertResultEquals(Result::noResult(null), $sut->filter(null));
    }

    #[Test]
    public function itDefersNonNulls(): void
    {
        $alternativeFilter = new ToThis();

        $sut = new Filter\Type\NullOr($alternativeFilter);

        self::assertResultEquals(
            Result::noResult($alternativeFilter),
            $sut->filter('Hello, World!')
        );
    }

    #[Test, DataProvider('provideAlternativeFilters')]
    public function itIsStringable(Filter $alternativeFilter,): void
    {
        $expected = "Accept null or $alternativeFilter";

        $sut = new Filter\Type\NullOr($alternativeFilter);

        self::assertSame($expected, $sut->__toString());
    }

    #[Test, DataProvider('provideAlternativeFilters')]
    public function itIsPHPStringable(Filter $alternativeFilter): void
    {
        $sut = new Filter\Type\NullOr($alternativeFilter);

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function provideAlternativeFilters(): Generator
    {
        yield [new Filter\Type\ToBool()];
        yield [new Filter\String\ToPascalCase()];
        yield [new Filter\Type\NullOr(new Filter\Type\ToBool())];
    }
}
