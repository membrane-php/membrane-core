<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Filter;
use Membrane\Filter\String\LeftTrim;
use Membrane\Filter\Type\ToFloat;
use Membrane\Filter\Type\ToInt;
use Membrane\Filter\Type\ToNumber;
use Membrane\OpenAPI\Filter\FormatStyle\Form;
use Membrane\OpenAPI\Filter\FormatStyle\Matrix;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Value;
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

final readonly class Numeric
{
    public function build(
        string $fieldName,
        V30\Keywords | V31\Keywords $keywords,
        bool $convertFromString = false,
        bool $convertFromArray = false,
        ?string $style = null,
    ): Processor {
        $types = $keywords->types;
        if (in_array(Type::Integer, $types)) {
            $type = Type::Integer->value;
        } elseif (in_array(Type::Number, $types)) {
            $type = Type::Number->value;
        } else {
            throw new \DomainException(sprintf(
                'numeric builder expected integer or number types, received: %s',
                implode(', ', array_map(fn($t) => $t->value, $types)),
            ));
        }

        $chain = $convertFromArray ?
            [new Filter\String\Implode(',')] :
            [];

        if (isset($style)) {
            switch (Style::tryFrom($style)) {
                case Style::Matrix:
                    $chain[] = new Matrix($type, false);
                    break;
                case Style::Label:
                    $chain[] = new LeftTrim('.');
                    break;
                case Style::Form:
                case Style::SpaceDelimited:
                case Style::PipeDelimited:
                    $chain[] = new Form($type, false);
                    break;
            }
        }

        $chain = array_merge($chain, $type === 'number'
            ? $this->handleNumber($keywords, $convertFromString)
            : $this->handleInteger($keywords, $convertFromString));

        if (
            $keywords->enum !== null
        ) {
            $chain[] = new Contained(array_map(
                fn(Value $v) => $v->value,
                $keywords->enum,
            ));
        }

        $chain = array_merge(
            $chain,
            $this->handleNumericConstraints($keywords),
        );

        return new Field($fieldName, ...$chain);
    }

    /** @return Filter[]|Validator[] */
    private function handleNumber(
        V30\Keywords | V31\Keywords $keywords,
        bool $convertFromString,
    ): array {
        if (in_array($keywords->format, ['float', 'double'], true)) {
            return $convertFromString ? [new NumericString(), new ToFloat()] : [new IsFloat()];
        } else {
            return $convertFromString ? [new NumericString(), new ToNumber()] : [new IsNumber()];
        }
    }

    /** @return Filter[]|Validator[] */
    private function handleInteger(
        V30\Keywords | V31\Keywords $keywords,
        bool $convertFromString,
    ): array {
        return $convertFromString ? [new IntString(), new ToInt()] : [new IsInt()];
    }

    /** @return Validator[] */
    private function handleNumericConstraints(
        V30\Keywords | V31\Keywords $keywords,
    ): array {
        $chain = [];
        // if keywords->maximum !== null
        // keywords->maximum->limit
        if ($keywords->maximum !== null) {
            $chain[] = new Maximum(
                $keywords->maximum->limit,
                $keywords->maximum->exclusive,
            );
        }

        if ($keywords->minimum !== null) {
            $chain[] = new Minimum(
                $keywords->minimum->limit,
                $keywords->minimum->exclusive,
            );
        }

        if ($keywords->multipleOf !== null) {
            $chain[] = new MultipleOf($keywords->multipleOf);
        }

        return $chain;
    }
}
