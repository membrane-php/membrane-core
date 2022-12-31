<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;

class TrueFalse extends APISchema
{
    public function __construct(string $fieldName, Schema $schema, public readonly bool $strict = true)
    {
        if ($schema->type !== 'boolean') {
            throw CannotProcessOpenAPI::mismatchedType(self::class, 'boolean', $schema->type);
        }

        parent::__construct($fieldName, $schema);
    }
}
