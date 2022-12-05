<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Exception;

class Strings extends APISchema
{
    public readonly ?int $maxLength;
    public readonly int $minLength;
    public readonly ?string $pattern;

    public function __construct(string $fieldName, Schema $schema)
    {
        if ($schema->type !== 'string') {
            throw new Exception('Strings Specification requires specified type of string');
        }

        $this->maxLength = $schema->maxLength;
        $this->minLength = $schema->minLength ?? 0;
        $this->pattern = $schema->pattern;

        parent::__construct($fieldName, $schema);
    }
}
