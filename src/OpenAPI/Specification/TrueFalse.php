<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Exception;

class TrueFalse extends APISchema
{
    public function __construct(string $fieldName, Schema $schema, public readonly bool $strict = true)
    {
        if ($schema->type !== 'boolean') {
            throw new Exception('TrueFalse Specification requires specified type of boolean');
        }

        parent::__construct($fieldName, $schema);
    }
}
