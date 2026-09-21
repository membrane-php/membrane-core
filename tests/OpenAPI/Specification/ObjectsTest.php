<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\Objects;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30, V31};
use Membrane\OpenAPIReader\ValueObject\Value;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Objects::class)]
#[CoversClass(APISchema::class)]
#[CoversClass(CannotProcessSpecification::class)]
class ObjectsTest extends TestCase
{
    #[Test]
    public function throwsExceptionForMissingType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(['object'], []));

        new Objects(
            '',
            new V30\Schema(new Identifier('test'), new Partial\Schema())->value,
        );
    }

    #[Test]
    public function throwsExceptionForIncorrectType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(['object'], ['string']));

        new Objects(
            '',
            new V30\Schema(new Identifier('test'), new Partial\Schema(type: 'string'))->value,
        );
    }
}
