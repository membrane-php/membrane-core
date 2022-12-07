<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Builder\Specification;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\String\DateString;
use Membrane\Validator\String\Length;
use Membrane\Validator\String\Regex;
use Membrane\Validator\Type\IsString;

class Strings extends APIBuilder
{
    public function supports(Specification $specification): bool
    {
        return $specification instanceof \Membrane\OpenAPI\Specification\Strings;
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof \Membrane\OpenAPI\Specification\Strings);

        $chain = [new IsString()];

        if ($specification->enum !== null) {
            $chain[] = new Contained($specification->enum);
        }

        if ($specification->format === 'date') {
            $chain[] = new DateString('Y-m-d');
        }

        if ($specification->format === 'date-time') {
            $chain[] = new DateString('Y-m-d\TH:i:sP');
        }

        if ($specification->minLength > 0 || $specification->maxLength !== null) {
            $chain[] = new Length($specification->minLength, $specification->maxLength);
        }

        if ($specification->pattern !== null) {
            $chain[] = new Regex($specification->pattern);
        }

        if ($specification->nullable) {
            return $this->handleNullable($specification->fieldName, new Field($specification->fieldName, ...$chain));
        }

        return new Field($specification->fieldName, ...$chain);
    }
}
