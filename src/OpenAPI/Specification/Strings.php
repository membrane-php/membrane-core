<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};

class Strings extends APISchema
{
    public readonly ?int $maxLength;
    public readonly int $minLength;
    public readonly ?string $pattern;

    public function __construct(
        OpenAPIVersion $openAPIVersion,
        string $fieldName,
        Schema $schema,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
    ) {
        //@todo get openapi version
        //@todo construct membrane schema

        if (is_array($schema->type)) {
            throw CannotProcessSpecification::arrayOfTypesIsUnsupported();
        }

        if ($schema->type !== 'string') {
            throw CannotProcessSpecification::mismatchedType(self::class, 'string', $schema->type);
        }

        $this->maxLength = $schema->maxLength;
        $this->minLength = $schema->minLength ?? 0;
        $this->pattern = $schema->pattern;

        parent::__construct($openAPIVersion, $fieldName, $schema);
    }
}
