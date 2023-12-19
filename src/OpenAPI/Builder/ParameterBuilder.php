<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Builder\Specification;
use Membrane\OpenAPI\Specification\Parameter;
use Membrane\Processor;

class ParameterBuilder extends APIBuilder
{
    public function supports(Specification $specification): bool
    {
        return $specification instanceof Parameter;
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof Parameter);

        return $this->fromParameter($specification);
    }

    public function fromParameter(
        Parameter $specification,
        bool $fromString = false,
        bool $fromArray = false,
    ): Processor {
        $schemaProcessor = $this->fromSchema(
            $specification->schema,
            $specification->name,
            $fromString,
            $fromArray,
            $specification->style
        );

        return $schemaProcessor;
    }
}
