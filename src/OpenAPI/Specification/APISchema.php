<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\Builder\Specification;

abstract class APISchema implements Specification
{
    /** @var mixed[] */
    public readonly ?array $enum;
    public readonly ?string $format;
    public readonly bool $nullable;

    public function __construct(
        public readonly string $fieldName,
        Schema $schema
    ) {
        $this->enum = $schema->enum;
        $this->format = $schema->format;
        $this->nullable = $schema->nullable ?? false;
    }
}
