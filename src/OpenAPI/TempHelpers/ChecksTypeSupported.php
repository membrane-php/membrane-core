<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\TempHelpers;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\Factory;
use Membrane\OpenAPIReader\ValueObject\Valid\{Enum\Type};

final class ChecksTypeSupported
{
    /** @param Type[]|Type|null $type */
    public static function check(array|Type|null $type): void
    {
        if (
            !is_array($type)
            || count($type) < 2
            || (count($type) === 2 && in_array(Type::Null, $type))
        ) {
            return;
        }

        throw CannotProcessSpecification::arrayOfTypesIsUnsupported();
    }
}
