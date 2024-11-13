<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\OpenAPIVersion;

class Numeric extends APISchema
{
    public readonly bool $exclusiveMaximum;
    public readonly bool $exclusiveMinimum;
    public readonly float | int | null $maximum;
    public readonly float | int | null $minimum;
    public readonly float | int | null $multipleOf;
    public readonly string $type;

    public function __construct(
        OpenAPIVersion $openAPIVersion,
        string $fieldName,
        Schema $schema,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
    ) {
        if (is_array($schema->type)) {
            throw CannotProcessSpecification::arrayOfTypesIsUnsupported();
        }

        if (!in_array($schema->type, ['number', 'integer'], true)) {
            throw CannotProcessSpecification::mismatchedType(self::class, 'integer or number', $schema->type);
        }

        $this->type = $schema->type;
        $this->exclusiveMaximum = $schema->exclusiveMaximum ?? false;
        $this->exclusiveMinimum = $schema->exclusiveMinimum ?? false;
        $this->maximum = $schema->maximum;
        $this->minimum = $schema->minimum;
        $this->multipleOf = $schema->multipleOf;

        parent::__construct($openAPIVersion, $fieldName, $schema);
    }
}
