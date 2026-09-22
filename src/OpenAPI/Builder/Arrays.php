<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Builder\Specification;
use Membrane\Filter;
use Membrane\OpenAPI\Filter\FormatStyle\Form;
use Membrane\OpenAPI\Filter\FormatStyle\Matrix;
use Membrane\OpenAPI\Filter\FormatStyle\PipeDelimited;
use Membrane\OpenAPI\Filter\FormatStyle\SpaceDelimited;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Value;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\Collection\Count;
use Membrane\Validator\Collection\Unique;
use Membrane\Validator\Type\IsList;

class Arrays extends APIBuilder
{
    public function build(
        string $fieldName,
        V30\Keywords | V31\Keywords $keywords,
        bool $convertFromString,
        bool $convertFromArray,
        ?string $style,
        ?bool $explode,
    ): Processor {
        if (!in_array(Type::Array, $keywords->types)) {
            throw new \DomainException(sprintf(
                'arrays builder expected array types, received: %s',
                implode(', ', array_map(
                    fn($t) => $t->value,
                    $keywords->types,
                )),
            ));
        }

        $beforeChain = $convertFromArray ?
            [new Filter\String\Implode(',')] :
            [];

        if (isset($style)) {
            $beforeChain = array_merge(
                $beforeChain,
                match (Style::from($style)) {
                    Style::Matrix => [
                        new Matrix('array', $explode ?? false),
                    ],
                    Style::Label => [
                        new Filter\String\LeftTrim('.'),
                        new Filter\String\Explode(
                            $explode ?? false ?
                                '.' :
                                ','
                        ),
                    ],
                    Style::Form => [
                        new Form('array', $explode ?? true),
                    ],
                    Style::Simple => [
                        new Filter\String\Explode(',')
                    ],
                    Style::SpaceDelimited => [new SpaceDelimited()],
                    Style::PipeDelimited => [new PipeDelimited()],
                    Style::DeepObject => [],
                },
            );
        }

        $beforeChain[] = new IsList();

        if (
            $keywords->enum !== null
        ) {
            $beforeChain[] = new Contained(array_map(
                fn(Value $v) => $v->value,
                $keywords->enum,
            ));
        }

        if (
            $keywords->minItems > 0
            || $keywords->maxItems !== null
        ) {
            $beforeChain[] = new Count(
                $keywords->minItems,
                $keywords->maxItems,
            );
        }

        if ($keywords->uniqueItems === true) {
            $beforeChain[] = new Unique();
        }

        $beforeSet = new BeforeSet(...$beforeChain);

        $collection = new Collection(
            $fieldName,
            $beforeSet,
            $this->fromSchema(
                $keywords->items,
                '',
                $convertFromString,
            )
        );

        return $collection;
    }
}
