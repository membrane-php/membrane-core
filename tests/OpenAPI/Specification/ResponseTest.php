<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use Membrane\OpenAPI\Specification\Response;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(Response::class)]
class ResponseTest extends TestCase
{
    #[Test, TestDox('constructs Response Specification')]
    public function constructTest(): void
    {
        $sut = new Response(
            __DIR__ . '/../../fixtures/OpenAPI/docs/petstore-expanded.json',
            'http://petstore.swagger.io/api/pets',
            Method::GET,
            '200'
        );

        self::assertInstanceOf(Response::class, $sut);
    }
}
