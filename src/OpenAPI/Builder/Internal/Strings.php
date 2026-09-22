<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder\Internal;

use Membrane\Filter\String\Implode;
use Membrane\Filter\String\LeftTrim;
use Membrane\Filter\String\ToUpperCase;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\Filter\FormatStyle\Form;
use Membrane\OpenAPI\Filter\FormatStyle\Matrix;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
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

/**
 * @internal see README.md
 */
final readonly class Strings
{
    public function build(
        string $fieldName,
        V30\Keywords | V31\Keywords $keywords,
        bool $convertFromArray = false,
        ?string $style = null,
    ): Processor {
        if (!in_array(Type::String, $keywords->types)) {
            throw CannotProcessSpecification::mismatchedType(
                ['string'],
                array_map(fn($t) => $t->value, $keywords->types),
            );
        }


        $chain = $convertFromArray ?
            [new Implode(',')] :
            [];

        if (isset($style)) {
            switch (Style::tryFrom($style)) {
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
            $keywords->enum !== null
        ) {
            $chain[] = new Contained(array_map(
                fn(Value $v) => $v->value,
                $keywords->enum,
            ));
        }

        if ($keywords->format === 'date') {
            $chain[] = new DateString('Y-m-d', true);
        }

        if ($keywords->format === 'date-time') {
            $chain[] = new ToUpperCase();
            $chain[] = new AnyOf(
                new DateString('Y-m-d\TH:i:sP', true),
                new DateString('Y-m-d\TH:i:sp', true),
            );
        }

        if (
            $keywords->maxLength !== null
            || $keywords->minLength > 0
        ) {
            $chain[] = new Length(
                $keywords->minLength,
                $keywords->maxLength,
            );
        }

        if ($keywords->pattern !== null) {
            $chain[] = new Regex(sprintf(
                '#%s#u',
                str_replace('#', '\#', $keywords->pattern),
            ));
        }

        return new Field($fieldName, ...$chain);
    }
}
