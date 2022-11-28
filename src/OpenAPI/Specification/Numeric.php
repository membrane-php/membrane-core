<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Exception;

class Numeric extends APISchema
{
    public readonly bool $exclusiveMaximum;
    public readonly bool $exclusiveMinimum;
    public readonly float|int|null $maximum;
    public readonly float|int|null $minimum;
    public readonly float|int|null $multipleOf;
    public readonly string $type;

    public function __construct(string $fieldName, Schema $schema, public readonly bool $strict = true)
    {
        if (!in_array($schema->type, ['number', 'integer'], true)) {
            throw new Exception('Numeric Specification requires specified type of integer or number');
        }

        $this->type = $schema->type;
        $this->exclusiveMaximum = $schema->exclusiveMaximum ?? false;
        $this->exclusiveMinimum = $schema->exclusiveMinimum ?? false;
        $this->maximum = $schema->maximum;
        $this->minimum = $schema->minimum;
        $this->multipleOf = $schema->multipleOf;

        parent::__construct($fieldName, $schema);
    }
}
