<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use cebe\openapi\spec\Schema;
use Membrane\Builder\Specification;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\Collection\Count;
use Membrane\Validator\Collection\Unique;
use Membrane\Validator\Type\IsList;

class Arrays extends APIBuilder
{
    public function supports(Specification $specification): bool
    {
        return $specification instanceof \Membrane\OpenAPI\Specification\Arrays;
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof \Membrane\OpenAPI\Specification\Arrays);

        $beforeChain = [new IsList()];

        if ($specification->enum !== null) {
            $beforeChain[] = new Contained($specification->enum);
        }

        if ($specification->minItems > 0 || $specification->maxItems !== null) {
            $beforeChain[] = new Count($specification->minItems, $specification->maxItems);
        }

        if ($specification->uniqueItems === true) {
            $beforeChain[] = new Unique();
        }

        $beforeSet = new BeforeSet(...$beforeChain);

        if ($specification->items === null) {
            return new Collection($specification->fieldName, $beforeSet);
        }

        assert($specification->items instanceof Schema);
        $collection = new Collection($specification->fieldName, $beforeSet, $this->fromSchema($specification->items));

        if ($specification->nullable) {
            return $this->handleNullable($specification->fieldName, $collection);
        }

        return $collection;
    }
}
