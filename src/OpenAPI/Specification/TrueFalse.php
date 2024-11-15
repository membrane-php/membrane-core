<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\TempHelpers\ChecksOnlyTypeOrNull;
use Membrane\OpenAPI\TempHelpers\CreatesSchema;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;

class TrueFalse extends APISchema
{
    public function __construct(
        OpenAPIVersion $openAPIVersion,
        string $fieldName,
        Schema $schema,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
    ) {
        $membraneSchema = CreatesSchema::create($openAPIVersion, $fieldName, $schema);

        ChecksOnlyTypeOrNull::check(
            self::class,
            Type::Boolean,
            $membraneSchema->type
        );

        parent::__construct($openAPIVersion, $fieldName, $membraneSchema);
    }
}
