<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\Arrays;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30, V31};
use Membrane\OpenAPIReader\ValueObject\Value;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Arrays::class)]
#[CoversClass(APISchema::class)]
#[CoversClass(CannotProcessSpecification::class)]
class ArraysTest extends TestCase
{
    #[Test]
    public function throwsExceptionForMissingType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(['array'], []));

        new Arrays(
            '',
            new V30\Schema(new Identifier('test'), new Partial\Schema())->value,
        );
    }

    #[Test]
    public function throwsExceptionForInvalidType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(['array'], ['string']));

        new Arrays(
            '',
            new V30\Schema(new Identifier('test'), new Partial\Schema(type: 'string'))->value,
        );
    }
}
