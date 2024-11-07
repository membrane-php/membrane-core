<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;

class Strings extends APISchema
{
    public readonly ?int $maxLength;
    public readonly int $minLength;
    public readonly ?string $pattern;

    public function __construct(
        string $fieldName,
        Schema $schema,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
    ) {
        if (is_array($schema->type)) {
            throw CannotProcessSpecification::arrayOfTypesIsUnsupported();
        }

        if ($schema->type !== 'string') {
            throw CannotProcessSpecification::mismatchedType(self::class, 'string', $schema->type);
        }

        $this->maxLength = $schema->maxLength;
        $this->minLength = $schema->minLength ?? 0;
        $this->pattern = $schema->pattern;

        parent::__construct($fieldName, $schema);
    }
}
