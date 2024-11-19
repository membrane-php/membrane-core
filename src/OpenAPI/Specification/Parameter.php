<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\Builder\Specification;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};

class Parameter implements Specification
{
    public readonly string $name;
    public readonly string $in;
    public readonly bool $required;
    public readonly string $style;
    public readonly bool $explode;
    public readonly V30\Schema|V31\Schema $schema;

    public function __construct(
        public readonly OpenAPIVersion $openAPIVersion,
        V30\Parameter|V31\Parameter $parameter,
    ) {
        $this->name = $parameter->name;
        $this->in = $parameter->in->value;
        $this->style = $parameter->style->value;
        $this->explode = $parameter->explode;

        if ($parameter->hasMediaType() && $parameter->getMediaType() !== 'application/json') {
            assert($parameter->getMediaType() !== null);
            throw CannotProcessOpenAPI::unsupportedMediaTypes($parameter->getMediaType());
        }

        $this->schema = $parameter->getSchema();

        $this->required = $parameter->required;
    }
}
