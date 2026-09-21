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
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Value;
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
        if (!in_array(Type::String, $specification->keywords->types)) {
            throw new \DomainException(sprintf(
                'strings builder expected string types, received: %s',
                implode(', ', array_map(
                    fn($t) => $t->value,
                    $specification->keywords->types,
                )),
            ));
        }


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

        if (
            $specification->keywords->enum !== null
        ) {
            $chain[] = new Contained(array_map(
                fn(Value $v) => $v->value,
                $specification->keywords->enum,
            ));
        }

        if ($specification->keywords->format === 'date') {
            $chain[] = new DateString('Y-m-d', true);
        }

        if ($specification->keywords->format === 'date-time') {
            $chain[] = new ToUpperCase();
            $chain[] = new AnyOf(
                new DateString('Y-m-d\TH:i:sP', true),
                new DateString('Y-m-d\TH:i:sp', true),
            );
        }

        if (
            $specification->keywords->maxLength !== null
            || $specification->keywords->minLength > 0
        ) {
            $chain[] = new Length(
                $specification->keywords->minLength,
                $specification->keywords->maxLength,
            );
        }

        if ($specification->keywords->pattern !== null) {
            $chain[] = new Regex(sprintf(
                '#%s#u',
                str_replace('#', '\#', $specification->keywords->pattern),
            ));
        }

        return new Field($specification->fieldName, ...$chain);
    }
}
