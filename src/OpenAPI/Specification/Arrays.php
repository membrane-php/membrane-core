<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Exception;

class Arrays extends APISchema
{
    public readonly ?Schema $items;
    public readonly ?int $maxItems;
    public readonly int $minItems;
    public readonly bool $uniqueItems;

    public function __construct(string $fieldName, Schema $schema)
    {
        if ($schema->type !== 'array') {
            throw new Exception('Arrays Specification requires specified type of array');
        }

        assert(!$schema->items instanceof Reference);
        $this->items = $schema->items;
        $this->maxItems = $schema->maxItems;
        $this->minItems = $schema->minItems ?? 0;
        $this->uniqueItems = $schema->uniqueItems;

        parent::__construct($fieldName, $schema);
    }
}
