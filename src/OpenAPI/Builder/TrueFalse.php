<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Builder\Specification;
use Membrane\Filter\Type\ToBool;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\Type\IsBool;
use Membrane\Validator\Type\IsBoolString;

class TrueFalse extends APIBuilder
{
    public function supports(Specification $specification): bool
    {
        return $specification instanceof \Membrane\OpenAPI\Specification\TrueFalse;
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof \Membrane\OpenAPI\Specification\TrueFalse);

        $chain = $specification->strict ? [new IsBool()] : [new IsBoolString(), new ToBool()];

        if ($specification->enum !== null) {
            $chain[] = new Contained($specification->enum);
        }

        if ($specification->nullable) {
            return $this->handleNullable($specification->fieldName, new Field($specification->fieldName, ...$chain));
        }

        return new Field($specification->fieldName, ...$chain);
    }
}
