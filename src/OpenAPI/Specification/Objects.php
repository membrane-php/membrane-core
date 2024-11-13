<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec as Cebe;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\OpenAPIVersion;

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
        if (is_array($schema->type)) {
            throw CannotProcessSpecification::arrayOfTypesIsUnsupported();
        }

        if ($schema->type !== 'object') {
            throw CannotProcessSpecification::mismatchedType(self::class, 'object', $schema->type);
        }

        assert(!$schema->additionalProperties instanceof Cebe\Reference);
        $this->additionalProperties = $schema->additionalProperties;

        $this->properties = array_filter($schema->properties ?? [], fn($p) => $p instanceof Cebe\Schema);

        $this->required = $schema->required;

        $this->maxProperties = $schema->maxProperties ?? null;

        $this->minProperties = $schema->minProperties ?? 0;

        parent::__construct($openAPIVersion, $fieldName, $schema);
    }
}
