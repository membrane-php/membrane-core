<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec as Cebe;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;

class Arrays extends APISchema
{
    public readonly ?Cebe\Schema $items;
    public readonly ?int $maxItems;
    public readonly int $minItems;
    public readonly bool $uniqueItems;

    public function __construct(
        string $fieldName,
        Cebe\Schema $schema,
        public readonly bool $convertFromString = false,
        public readonly ?string $style = null
    ) {
        if ($schema->type !== 'array') {
            throw CannotProcessSpecification::mismatchedType(self::class, 'array', $schema->type);
        }

        assert(!$schema->items instanceof Cebe\Reference);
        $this->items = $schema->items;
        $this->maxItems = $schema->maxItems;
        $this->minItems = $schema->minItems ?? 0;
        $this->uniqueItems = $schema->uniqueItems;

        parent::__construct($fieldName, $schema);
    }
}
