<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\TempHelpers\CreatesSchema;
use Membrane\OpenAPIReader\OpenAPIVersion;
use cebe\openapi\spec\Schema;
use Membrane\Builder\Specification;

abstract class APISchema implements Specification
{
    /** @var mixed[] */
    public readonly ?array $enum;
    public readonly ?string $format;
    public readonly bool $nullable;

    public function __construct(
        public readonly OpenAPIVersion $openAPIVersion,
        public readonly string $fieldName,
        Schema $schema
    ) {
        $membraneSchema = CreatesSchema::create($openAPIVersion, $fieldName, $schema);

        $this->enum = isset($membraneSchema->enum) ?
            array_map(fn($e) => $e->value, $membraneSchema->enum) :
            null;
        $this->format = $schema->format;
        $this->nullable = $schema->nullable ?? false;
    }
}
