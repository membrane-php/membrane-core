<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;

class Objects extends APISchema
{
    // @TODO support minProperties and maxProperties
    /** @var Schema[] */
    public readonly array $properties;
    /** @var string[]|null */
    public readonly ?array $required;

    public function __construct(string $fieldName, Schema $schema)
    {
        if ($schema->type !== 'object') {
            throw CannotProcessOpenAPI::mismatchedType(self::class, 'object', $schema->type);
        }

        $this->properties = array_filter($schema->properties ?? [], fn($p) => $p instanceof Schema);

        $this->required = $schema->required;

        parent::__construct($fieldName, $schema);
    }
}
