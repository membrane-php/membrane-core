<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec as Cebe;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\OpenAPIVersion;

class Arrays extends APISchema
{
    public readonly ?Cebe\Schema $items;
    public readonly ?int $maxItems;
    public readonly int $minItems;
    public readonly bool $uniqueItems;

    public function __construct(
        OpenAPIVersion $openAPIVersion,
        string $fieldName,
        Cebe\Schema $schema,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
        public readonly ?bool $explode = null,
    ) {
        if (is_array($schema->type)) {
            throw CannotProcessSpecification::arrayOfTypesIsUnsupported();
        }

        if ($schema->type !== 'array') {
            throw CannotProcessSpecification::mismatchedType(self::class, 'array', $schema->type);
        }

        assert(!$schema->items instanceof Cebe\Reference);
        $this->items = $schema->items;
        $this->maxItems = $schema->maxItems;
        $this->minItems = $schema->minItems ?? 0;
        $this->uniqueItems = $schema->uniqueItems;

        parent::__construct($openAPIVersion, $fieldName, $schema);
    }
}
