<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\TempHelpers;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\Factory;
use Membrane\OpenAPIReader\ValueObject\Valid\{Enum\Type};

final class ChecksNumericTypeOrNull
{
    /** @param Type[]|Type|null $type */
    public static function check(
        string $className,
        array|Type|null $type
    ): void {
        if (!is_array($type)) {
            if ($type === Type::Integer || $type === Type::Number) {
                return;
            }
            throw CannotProcessSpecification::mismatchedType(
                $className,
                'integer or number',
                $type?->value,
            );
        }

        if (
            count($type) > 2 || (count($type) === 2 && ! in_array(Type::Null, $type))
        ) {
            throw CannotProcessSpecification::arrayOfTypesIsUnsupported();
        }

        if (!in_array(Type::Integer, $type) && !in_array(Type::Number, $type)) {
            throw CannotProcessSpecification::mismatchedType(
                $className,
                'integer or number',
                implode(', ', array_map(fn($t) => $t->value, $type)),
            );
        }
    }
}
