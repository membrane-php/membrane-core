<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Builder\Specification;
use Membrane\Filter;
use Membrane\Filter\Type\ToFloat;
use Membrane\Filter\Type\ToInt;
use Membrane\Filter\Type\ToNumber;
use Membrane\OpenAPI;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Validator;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\Numeric\Maximum;
use Membrane\Validator\Numeric\Minimum;
use Membrane\Validator\Numeric\MultipleOf;
use Membrane\Validator\Type\IsFloat;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsNumber;

class Numeric extends APIBuilder
{
    public function supports(Specification $specification): bool
    {
        return $specification instanceof OpenAPI\Specification\Numeric;
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof OpenAPI\Specification\Numeric);

        if ($specification->type === 'number') {
            $chain = $this->handleNumber($specification);
        } else {
            $chain = $this->handleInteger($specification);
        }

        if ($specification->enum !== null) {
            $chain[] = new Contained($specification->enum);
        }

        $chain = array_merge($chain, $this->handleNumericConstraints($specification));

        if ($specification->nullable) {
            return $this->handleNullable($specification->fieldName, new Field($specification->fieldName, ...$chain));
        }

        return new Field($specification->fieldName, ...$chain);
    }

    /** @return Filter[]|Validator[] */
    private function handleNumber(OpenAPI\Specification\Numeric $specification): array
    {
        if (in_array($specification->format, ['float', 'double'], true)) {
            return $specification->strict ? [new IsFloat()] : [new ToFloat(), new IsFloat()];
        } else {
            return $specification->strict ? [new IsNumber()] : [new ToNumber(), new IsNumber()];
        }
    }

    /** @return Filter[]|Validator[] */
    private function handleInteger(OpenAPI\Specification\Numeric $specification): array
    {
        return $specification->strict ? [new IsInt()] : [new ToInt(), new IsInt()];
    }

    /** @return Validator[] */
    private function handleNumericConstraints(OpenAPI\Specification\Numeric $specification): array
    {
        $chain = [];

        if ($specification->maximum !== null) {
            $chain[] = new Maximum($specification->maximum, $specification->exclusiveMaximum);
        }

        if ($specification->minimum !== null) {
            $chain[] = new Minimum($specification->minimum, $specification->exclusiveMinimum);
        }

        if ($specification->multipleOf !== null) {
            $chain[] = new MultipleOf($specification->multipleOf);
        }

        return $chain;
    }
}
