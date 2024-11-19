<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\TempHelpers;

use cebe\openapi\spec as Cebe;
use Membrane\OpenAPIReader\Factory;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;

final class CreatesParameters
{
    /**
     * @param Cebe\Parameter[]|Cebe\Reference[] $parameters
     * @return V30\Parameter[]|V31\Parameter[]
     */
    public static function create(
        OpenAPIVersion $openAPIVersion,
        array $parameters
    ): array {
        return match($openAPIVersion) {
            OpenAPIVersion::Version_3_0 => array_map(
                fn($p) => new V30\Parameter(new Identifier($p->name ?? ''), $p),
                Factory\V30\FromCebe::createParameters($parameters),
            ),
            OpenAPIVersion::Version_3_1 => array_map(
                fn($p) => new V31\Parameter(new Identifier($p->name ?? ''), $p),
                Factory\V31\FromCebe::createParameters($parameters),
            ),
        };
    }
}
