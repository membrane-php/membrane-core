<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\OpenAPI;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MakesPathItem::class)]
class MakesPathItemTest extends TestCase
{
    #[Test]
    #[DataProvider('provideGet')]
    public function itCanMakeAPathItemWithGet(
        array $expected,
        ?MakesOperation $get,
    ): void {
        $sut = new MakesPathItem($get);

        self::assertEquals($expected, $sut->jsonSerialize());
    }

    /** @return Generator<array{ 0: array<mixed>, 1: array<mixed> }> */
    public static function provideGet(): Generator
    {
        yield 'nothing' => [[], null];

        yield 'something' => [
            [
                'get' => (new MakesOperation())->jsonSerialize(),
            ],
            new MakesOperation(),
        ];
    }
}
