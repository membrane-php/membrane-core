<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec as Cebe;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\TempHelpers\ChecksOnlyTypeOrNull;
use Membrane\OpenAPI\TempHelpers\CreatesSchema;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;

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
        $membraneSchema = CreatesSchema::create($openAPIVersion, $fieldName, $schema);

        ChecksOnlyTypeOrNull::check(
            self::class,
            Type::Array,
            $membraneSchema->type
        );

        assert(!$schema->items instanceof Cebe\Reference);
        // @todo replace items with membrane schema
        $this->items = $schema->items;
        $this->maxItems = $membraneSchema->maxItems;
        $this->minItems = $membraneSchema->minItems;
        $this->uniqueItems = $membraneSchema->uniqueItems;

        parent::__construct($openAPIVersion, $fieldName, $membraneSchema);
    }
}
