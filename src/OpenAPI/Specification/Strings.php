<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\TempHelpers\ChecksOnlyTypeOrNull;
use Membrane\OpenAPI\TempHelpers\CreatesSchema;
use Membrane\OpenAPIReader\Factory;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{Enum\Type, Identifier, V30, V31};

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
        $membraneSchema = CreatesSchema::create($openAPIVersion, $fieldName, $schema);

        ChecksOnlyTypeOrNull::check(
            self::class,
            Type::String,
            $membraneSchema->type
        );

        $this->maxLength = $membraneSchema->maxLength;
        $this->minLength = $membraneSchema->minLength;
        $this->pattern = $membraneSchema->pattern;

        parent::__construct($openAPIVersion, $fieldName, $membraneSchema);
    }
}
