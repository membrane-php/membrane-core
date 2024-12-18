<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\TempHelpers;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\Factory;
use Membrane\OpenAPIReader\ValueObject\Valid\{Enum\Type};

final class ChecksOnlyTypeOrNull
{
    /** @param Type[] $types */
    public static function check(
        string $className,
        Type $typeItShouldBe,
        array $types
    ): void {
        switch (count($types)) {
            case 0:
                throw CannotProcessSpecification::unspecifiedType('', '');
            case 1:
                if (!in_array($typeItShouldBe, $types)) {
                    throw CannotProcessSpecification::mismatchedType(
                        $className,
                        $typeItShouldBe->value,
                        $types[0]->value,
                    );
                }
                break;
            case 2:
                if (!in_array($typeItShouldBe, $types)) {
                    throw CannotProcessSpecification::mismatchedType(
                        $className,
                        $typeItShouldBe->value,
                        $types[0]->value,
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
