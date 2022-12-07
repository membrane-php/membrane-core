<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use cebe\openapi\spec\Schema;
use Membrane\Builder\Specification;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\FieldSet;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Type\IsArray;

class Objects extends APIBuilder
{
    public function supports(Specification $specification): bool
    {
        return $specification instanceof \Membrane\OpenAPI\Specification\Objects;
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof \Membrane\OpenAPI\Specification\Objects);

        $beforeChain = [new IsArray()];

        if ($specification->enum !== null) {
            $beforeChain[] = new Contained($specification->enum);
        }

        if ($specification->required !== null) {
            $beforeChain[] = new RequiredFields(...$specification->required);
        }

        // @TODO support minProperties and maxProperties

        $beforeSet = new BeforeSet(...$beforeChain);

        $fields = [];
        foreach ($specification->properties as $key => $value) {
            assert($value instanceof Schema);
            $fields [] = $this->fromSchema($value, $key);
        }

        $processor = new FieldSet($specification->fieldName, $beforeSet, ...$fields);

        if ($specification->nullable) {
            return $this->handleNullable($specification->fieldName, $processor);
        }

        return $processor;
    }
}
