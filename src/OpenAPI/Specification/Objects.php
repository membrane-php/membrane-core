<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec as Cebe;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\TempHelpers\ChecksOnlyTypeOrNull;
use Membrane\OpenAPI\TempHelpers\CreatesSchema;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;

class Objects extends APISchema
{
    // @TODO support minProperties and maxProperties
    public readonly bool | Cebe\Schema $additionalProperties;
    /** @var Cebe\Schema[] */
    public readonly array $properties;
    /** @var string[]|null */
    public readonly ?array $required;

    public readonly ?int $maxProperties;
    public readonly int $minProperties;

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
            Type::Object,
            $membraneSchema->type
        );

        //@todo replace additionalProperties with membrane schema
        assert(!$schema->additionalProperties instanceof Cebe\Reference);
        $this->additionalProperties = $schema->additionalProperties;

        //@todo replace properties with membrane schema array
        $this->properties = array_filter($schema->properties ?? [], fn($p) => $p instanceof Cebe\Schema);

        $this->required = $membraneSchema->required;

        $this->maxProperties = $membraneSchema->maxProperties;

        $this->minProperties = $membraneSchema->minProperties;

        parent::__construct($openAPIVersion, $fieldName, $membraneSchema);
    }
}
