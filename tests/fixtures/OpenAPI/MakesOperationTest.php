<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\OpenAPI;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MakesOperation::class)]
class MakesOperationTest extends TestCase
{
    #[Test]
    public function itCanMakeAnOperationWithoutParameters(): void
    {
        $expected = [
            'operationId' => 'test',
            'responses' => ['200' => ['description' => 'Success']],
        ];

        $sut = new MakesOperation();

        self::assertEquals($expected, $sut->jsonSerialize());
    }

    #[Test]
    public function itCanMakeAnOperationWithAHeader(): void
    {
        $data = [
            'name' => 'test-header',
            'required' => true,
            'explode' => true,
            'schema' => true,
        ];
        $expected = [
            'operationId' => 'test',
            'parameters' => [['in' => 'header', 'style' => 'simple', ...$data]],
            'responses' => ['200' => ['description' => 'Success']],
        ];

        $sut = MakesOperation::withHeaderParameter(...$data);

        self::assertEquals($expected, $sut->jsonSerialize());
    }

    /** @return Generator<array{ 0: array<mixed>, 1: array<mixed> }> */
    public static function provideHeaders(): Generator
    {
        $api = fn($headers = []) => [

        ];

        $dataSet = fn($header = []) => [$api($header), $header];

        yield 'one parameter' => $dataSet();
    }
}
