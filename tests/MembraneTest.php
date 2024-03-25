<?php

declare(strict_types=1);

namespace Membrane\Tests;

use Generator;
use Membrane\Attribute\ClassWithAttributes;
use Membrane\Builder\Specification;
use Membrane\Membrane;
use Membrane\OpenAPI\Specification\Request;
use Membrane\OpenAPI\Specification\Response;
use Membrane\OpenAPIReader\Method;
use Membrane\Result\Result;
use Membrane\Tests\Fixtures\Attribute\EmptyClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Membrane::class)]
class MembraneTest extends TestCase
{
    private const __FIXTURES__ = __DIR__ . '/fixtures/';

    public static function provideSpecifications(): Generator
    {
        yield 'Attributes' => [
            new class {
            },
            new ClassWithAttributes(EmptyClass::class),
        ];
        yield 'Request' => [
            new \GuzzleHttp\Psr7\Request('get', ''),
            new Request(self::__FIXTURES__ . 'OpenAPI/hatstore.json', '/hats', Method::GET),
        ];
        yield 'Response' => [
            new \GuzzleHttp\Psr7\Response(),
            new Response(self::__FIXTURES__ . 'OpenAPI/hatstore.json', '/hats', Method::GET, '200'),
        ];
    }

    #[Test, DataProvider('provideSpecifications')]
    public function itMayAllowDefaultBuilders(
        mixed $value,
        Specification $specification
    ): void {
        $sut = new Membrane();

        self::assertInstanceOf(
            Result::class,
            $sut->process($value, $specification)
        );
    }

    #[Test, DataProvider('provideSpecifications')]
    public function itMayDisallowDefaultBuilders(
        mixed $value,
        Specification $specification
    ): void {
        $sut = Membrane::withoutDefaults();

        self::expectException(RuntimeException::class);

        $sut->process($value, $specification);
    }
}
