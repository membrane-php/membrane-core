<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\TempHelpers;

use Membrane\OpenAPIReader\Factory;
use Membrane\OpenAPIReader\ValueObject\Valid\{Enum\Type};

final class ChecksNullable
{
    /** @param Type[]|Type|null $types */
    public static function check(array|Type|null $types): bool
    {
        return $types !== null
            && $types === Type::Null
            || (is_array($types) && in_array(Type::Null, $types));
    }
}
