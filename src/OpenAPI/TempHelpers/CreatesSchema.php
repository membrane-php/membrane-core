<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\TempHelpers;

use cebe\openapi\spec as Cebe;
use Membrane\OpenAPIReader\Factory;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use RuntimeException;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};

final class CreatesSchema
{
    public static function create(
        OpenAPIVersion $openAPIVersion,
        string $fieldName,
        Cebe\Schema $schema
    ): V30\Schema|V31\Schema {
        return match($openAPIVersion) {
            OpenAPIVersion::Version_3_0 => new V30\Schema(
                new Identifier($fieldName),
                Factory\V30\FromCebe::createSchema($schema) ?? throw new RuntimeException('could not make schema'),
            ),
            OpenAPIVersion::Version_3_1 => new V31\Schema(
                new Identifier($fieldName),
                Factory\V31\FromCebe::createSchema($schema) ?? throw new RuntimeException('could not make schema'),
            )
        };
    }
}
