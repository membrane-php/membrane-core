<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder\Internal;

use Membrane\Filter\String\Implode;
use Membrane\Filter\String\LeftTrim;
use Membrane\Filter\Type\ToBool;
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
use Membrane\Validator\String\BoolString;
use Membrane\Validator\Type\IsBool;

/**
 * @internal see README.md
 */
final readonly class TrueFalse
{
    public function build(
        string $fieldName,
        V30\Keywords | V31\Keywords $keywords,
        bool $convertFromString = false,
        bool $convertFromArray = false,
        ?string $style = null,
    ): Processor {
        if (!in_array(Type::Boolean, $keywords->types)) {
            throw CannotProcessSpecification::mismatchedType(
                ['boolean'],
                array_map(fn($t) => $t->value, $keywords->types),
            );
        }

        $chain = $convertFromArray ?
            [new Implode(',')] :
            [];

        if (isset($style)) {
            switch (Style::tryFrom($style)) {
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

        $chain = array_merge($chain, $convertFromString ?
            [new BoolString(), new ToBool()] :
            [new IsBool()]);

        if (
            $keywords->enum !== null
        ) {
            $chain[] = new Contained(array_map(
                fn(Value $v) => $v->value,
                $keywords->enum,
            ));
        }

        return new Field($fieldName, ...$chain);
    }
}
