<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Filter;

use Membrane\OpenAPI\Filter\HTTPParameters;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HTTPParameters::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
class HTTPParametersTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'convert query string to a field set of query parameters';
        $sut = new HTTPParameters();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new HTTPParameters();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsToFilter(): array
    {
        return [
            [
                ['id' => '1'],
                Result::invalid(
                    ['id' => '1'],
                    new MessageSet(
                        null,
                        new Message('HTTPParameters expects string value, %s passed instead', ['array'])
                    )
                ),
            ],
            [
                'id=1',
                Result::valid(['id' => '1']),
            ],
            [
                'id=1&name=Ben',
                Result::valid(['id' => '1', 'name' => 'Ben']),
            ],
        ];
    }

    #[DataProvider('dataSetsToFilter')]
    #[Test]
    public function filterTest(mixed $input, Result $expected): void
    {
        $sut = new HTTPParameters();

        $actual = $sut->filter($input);

        self::assertEquals($expected, $actual);
    }
}
