<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec as Cebe;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;

class Parameter implements Specification
{
    public readonly string $name;
    public readonly string $in;
    public readonly bool $required;
    public readonly string $style;
    public readonly bool $explode;
    public readonly Cebe\Schema $schema;

    public function __construct(
        Cebe\Parameter $parameter
    ) {
        $this->name = $parameter->name;
        $this->in = $parameter->in;
        $this->style = $parameter->style;

        switch ($this->style) {
            case 'matrix':
            case 'label':
                if ($this->in !== 'path') {
                    throw CannotProcessOpenAPI::invalidStyleLocation($this->name, $this->style, $this->in);
                }
                break;
            case 'form':
                if (!in_array($this->in, ['query', 'cookie'])) {
                    throw CannotProcessOpenAPI::invalidStyleLocation($this->name, $this->style, $this->in);
                }
                break;
            case 'simple':
                if (!in_array($this->in, ['path', 'header'])) {
                    throw CannotProcessOpenAPI::invalidStyleLocation($this->name, $this->style, $this->in);
                }
                break;
            case 'spaceDelimited':
            case 'pipeDelimited':
            case 'deepObject':
                if ($this->in !== 'query') {
                    throw CannotProcessOpenAPI::invalidStyleLocation($this->name, $this->style, $this->in);
                }
                break;
        }

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

        // Cebe library already validates that parameters MUST have either a schema or content but not both.
        assert($schemaLocations instanceof Cebe\Schema);

        return $schemaLocations;
    }
}
