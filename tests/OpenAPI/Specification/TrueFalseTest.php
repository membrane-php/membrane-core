<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\Specification\APISchema;
use Membrane\OpenAPI\Specification\TrueFalse;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\{Identifier, V30};
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TrueFalse::class)]
#[CoversClass(APISchema::class)]
#[CoversClass(CannotProcessSpecification::class)]
class TrueFalseTest extends TestCase
{
    #[Test]
    public function throwsExceptionForMissingType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(['boolean'], []));

        new TrueFalse(
            '',
            new V30\Schema(new Identifier('test'), new Partial\Schema())->value,
        );
    }

    #[Test]
    public function throwsExceptionForInvalidType(): void
    {
        self::expectExceptionObject(CannotProcessSpecification::mismatchedType(['boolean'], ['string']));

        new TrueFalse(
            '',
            new V30\Schema(new Identifier('test'), new Partial\Schema(type: 'string'))->value,
        );
    }
}
