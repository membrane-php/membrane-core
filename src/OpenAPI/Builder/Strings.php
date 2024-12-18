<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Builder\Specification;
use Membrane\Filter\String\Explode;
use Membrane\Filter\String\Implode;
use Membrane\Filter\String\LeftTrim;
use Membrane\Filter\String\ToUpperCase;
use Membrane\OpenAPI\Filter\FormatStyle\Form;
use Membrane\OpenAPI\Filter\FormatStyle\Matrix;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\String\DateString;
use Membrane\Validator\String\Length;
use Membrane\Validator\String\Regex;
use Membrane\Validator\Type\IsString;
use Membrane\Validator\Utility\AnyOf;

class Strings extends APIBuilder
{
    public function supports(Specification $specification): bool
    {
        return $specification instanceof \Membrane\OpenAPI\Specification\Strings;
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof \Membrane\OpenAPI\Specification\Strings);

        $chain = $specification->convertFromArray ?
            [new Implode(',')] :
            [];

        if (isset($specification->style)) {
            switch (Style::tryFrom($specification->style)) {
                case Style::Matrix:
                    $chain[] = new Matrix('string', false);
                    break;
                case Style::Label:
                    $chain[] = new LeftTrim('.');
                    break;
                case Style::Form:
                case Style::SpaceDelimited:
                case Style::PipeDelimited:
                    $chain[] = new Form('string', false);
                    break;
            }
        }

        $chain[] = new IsString();

        if ($specification->enum !== null) {
            $chain[] = new Contained($specification->enum);
        }

        if ($specification->format === 'date') {
            $chain[] = new DateString('Y-m-d', true);
        }

        if ($specification->format === 'date-time') {
            $chain[] = new ToUpperCase();
            $chain[] = new AnyOf(
                new DateString('Y-m-d\TH:i:sP', true),
                new DateString('Y-m-d\TH:i:sp', true),
            );
        }

        if ($specification->minLength > 0 || $specification->maxLength !== null) {
            $chain[] = new Length($specification->minLength, $specification->maxLength);
        }

        if ($specification->pattern !== null) {
            $chain[] = new Regex(
                sprintf('#%s#u', str_replace('#', '\#', $specification->pattern))
            );
        }

        return new Field($specification->fieldName, ...$chain);
    }
}
