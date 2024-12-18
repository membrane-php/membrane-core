<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Builder\Specification;
use Membrane\Filter\String\Implode;
use Membrane\Filter\String\LeftTrim;
use Membrane\Filter\Type\ToBool;
use Membrane\OpenAPI\Filter\FormatStyle\Form;
use Membrane\OpenAPI\Filter\FormatStyle\Matrix;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\String\BoolString;
use Membrane\Validator\Type\IsBool;

class TrueFalse extends APIBuilder
{
    public function supports(Specification $specification): bool
    {
        return $specification instanceof \Membrane\OpenAPI\Specification\TrueFalse;
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof \Membrane\OpenAPI\Specification\TrueFalse);

        $chain = $specification->convertFromArray ?
            [new Implode(',')] :
            [];

        if (isset($specification->style)) {
            switch (Style::tryFrom($specification->style)) {
                case Style::Matrix:
                    $chain[] = new Matrix('boolean', false);
                    break;
                case Style::Label:
                    $chain[] = new LeftTrim('.');
                    break;
                case Style::Form:
                case Style::SpaceDelimited:
                case Style::PipeDelimited:
                    $chain[] = new Form('boolean', false);
                    break;
            }
        }

        $chain = array_merge($chain, $specification->convertFromString ?
            [new BoolString(), new ToBool()] :
            [new IsBool()]);

        if ($specification->enum !== null) {
            $chain[] = new Contained($specification->enum);
        }

        return new Field($specification->fieldName, ...$chain);
    }
}
