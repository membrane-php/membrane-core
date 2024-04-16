<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;

class TrueFalse extends APISchema
{
    public function __construct(
        string $fieldName,
        Schema $schema,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
    ) {
        if ($schema->type !== 'boolean') {
            throw CannotProcessSpecification::mismatchedType(self::class, 'boolean', $schema->type);
        }

        parent::__construct($fieldName, $schema);
    }
}
