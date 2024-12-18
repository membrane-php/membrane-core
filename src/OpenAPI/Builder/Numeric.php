<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Builder\Specification;
use Membrane\Filter;
use Membrane\Filter\String\LeftTrim;
use Membrane\Filter\Type\ToFloat;
use Membrane\Filter\Type\ToInt;
use Membrane\Filter\Type\ToNumber;
use Membrane\OpenAPI;
use Membrane\OpenAPI\Filter\FormatStyle\Form;
use Membrane\OpenAPI\Filter\FormatStyle\Matrix;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Validator;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\Numeric\Maximum;
use Membrane\Validator\Numeric\Minimum;
use Membrane\Validator\Numeric\MultipleOf;
use Membrane\Validator\String\IntString;
use Membrane\Validator\String\NumericString;
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

        $chain = $specification->convertFromArray ?
            [new Filter\String\Implode(',')] :
            [];

        if (isset($specification->style)) {
            switch (Style::tryFrom($specification->style)) {
                case Style::Matrix:
                    $chain[] = new Matrix($specification->type, false);
                    break;
                case Style::Label:
                    $chain[] = new LeftTrim('.');
                    break;
                case Style::Form:
                case Style::SpaceDelimited:
                case Style::PipeDelimited:
                    $chain[] = new Form($specification->type, false);
                    break;
            }
        }

        $chain = array_merge($chain, $specification->type === 'number' ?
            $this->handleNumber($specification) :
            $this->handleInteger($specification));

        if ($specification->enum !== null) {
            $chain[] = new Contained($specification->enum);
        }

        $chain = array_merge($chain, $this->handleNumericConstraints($specification));

        return new Field($specification->fieldName, ...$chain);
    }

    /** @return Filter[]|Validator[] */
    private function handleNumber(OpenAPI\Specification\Numeric $specification): array
    {
        if (in_array($specification->format, ['float', 'double'], true)) {
            return $specification->convertFromString ? [new NumericString(), new ToFloat()] : [new IsFloat()];
        } else {
            return $specification->convertFromString ? [new NumericString(), new ToNumber()] : [new IsNumber()];
        }
    }

    /** @return Filter[]|Validator[] */
    private function handleInteger(OpenAPI\Specification\Numeric $specification): array
    {
        return $specification->convertFromString ? [new IntString(), new ToInt()] : [new IsInt()];
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
