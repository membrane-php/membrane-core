<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\TempHelpers;

use cebe\openapi\spec as Cebe;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\Factory;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{Enum\Type, V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use RuntimeException;

final class ChecksOnlyTypeOrNull
{
    /** @param Type[]|Type|null $types */
    public static function check(
        string $className,
        Type $typeItShouldBe,
        array|Type|null $types
    ): void {
        if (!is_array($types)) {
            if ($types !== $typeItShouldBe) {
                throw CannotProcessSpecification::mismatchedType(
                    $className,
                    $typeItShouldBe->value,
                    $types?->value,
                );
            }
        } elseif (
            count($types) === 1
            && $types !== [$typeItShouldBe]
        ) {
            throw CannotProcessSpecification::mismatchedType(
                $className,
                $typeItShouldBe->value,
                $types[0]->value,
            );
        } elseif (
            count($types) === 2
            && in_array(Type::Null, $types)
            && in_array($typeItShouldBe, $types)
        ) {
            throw CannotProcessSpecification::arrayOfTypesIsUnsupported();
        }
    }
}
