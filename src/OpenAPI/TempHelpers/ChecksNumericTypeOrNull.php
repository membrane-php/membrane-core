<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\TempHelpers;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\Factory;
use Membrane\OpenAPIReader\ValueObject\Valid\{Enum\Type};

final class ChecksNumericTypeOrNull
{
    /** @param Type[] $type */
    public static function check(
        string $className,
        array $types
    ): void {
        switch (count($types)) {
            case 0:
                throw CannotProcessSpecification::unspecifiedType($className, 'integer or number');
            case 1:
                if (!in_array(Type::Integer, $types) && !in_array(Type::Number, $types)) {
                    throw CannotProcessSpecification::mismatchedType(
                        $className,
                        'integer or number',
                        implode(', ', array_map(fn($t) => $t->value, $types)),
                    );
                }
                break;
            case 2:
                if (!in_array(Type::Integer, $types) && !in_array(Type::Number, $types)) {
                    throw CannotProcessSpecification::mismatchedType(
                        $className,
                        'integer or number',
                        implode(', ', array_map(fn($t) => $t->value, $types)),
                    );
                }
                if (!in_array(Type::Null, $types)) {
                    throw CannotProcessSpecification::arrayOfTypesIsUnsupported();
                }
                break;
            default:
                throw CannotProcessSpecification::arrayOfTypesIsUnsupported();
        }
    }
}
