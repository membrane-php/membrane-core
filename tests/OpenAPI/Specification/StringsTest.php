<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\Strings;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30};
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Strings::class)]
#[CoversClass(APISchema::class)]
#[CoversClass(CannotProcessSpecification::class)]
class StringsTest extends TestCase
{
    #[Test]
    public function throwsExceptionForMissingType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(['string'], []));

        new Strings(
            '',
            new V30\Schema(new Identifier('test'), new Partial\Schema())->value,
        );
    }

    #[Test]
    public function throwsExceptionForIncorrectType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(['string'], ['integer']));

        new Strings(
            '',
            new V30\Schema(new Identifier('test'), new Partial\Schema(type: 'integer'))->value,
        );
    }
}
