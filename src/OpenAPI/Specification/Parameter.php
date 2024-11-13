<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec as Cebe;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\In;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;

class Parameter implements Specification
{
    public readonly string $name;
    public readonly string $in;
    public readonly bool $required;
    public readonly string $style;
    public readonly bool $explode;
    public readonly Cebe\Schema $schema;

    public function __construct(
        OpenAPIVersion $openAPIVersion,
        Cebe\Parameter $parameter
    ) {
        $this->name = $parameter->name;
        $this->in = $parameter->in;
        $this->style = $parameter->style;
        $this->explode = $parameter->explode;
        $this->schema = $this->findSchema($parameter);

        $this->required = $parameter->required;
    }

    private function findSchema(Cebe\Parameter $parameter): Cebe\Schema
    {
        $schemaLocations = null;

        if ($parameter->schema !== null) {
            $schemaLocations = $parameter->schema;
        }

        if ($parameter->content !== []) {
            $schemaLocations = $parameter->content['application/json']?->schema
                ??
                throw CannotProcessOpenAPI::unsupportedMediaTypes(...array_keys($parameter->content));
        }

        // OpenAPI Reader validates parameters MUST have schema xor content.
        assert($schemaLocations instanceof Cebe\Schema);

        return $schemaLocations;
    }
}
