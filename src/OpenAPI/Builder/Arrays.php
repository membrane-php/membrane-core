<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Builder\Specification;
use Membrane\Filter;
use Membrane\OpenAPI\Filter\FormatStyle\Form;
use Membrane\OpenAPI\Filter\FormatStyle\Matrix;
use Membrane\OpenAPI\Filter\FormatStyle\PipeDelimited;
use Membrane\OpenAPI\Filter\FormatStyle\SpaceDelimited;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Collection;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\Collection\Count;
use Membrane\Validator\Collection\Unique;
use Membrane\Validator\Type\IsList;

class Arrays extends APIBuilder
{
    public function supports(Specification $specification): bool
    {
        return $specification instanceof \Membrane\OpenAPI\Specification\Arrays;
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof \Membrane\OpenAPI\Specification\Arrays);

        $beforeChain = $specification->convertFromArray ?
            [new Filter\String\Implode(',')] :
            [];

        if (isset($specification->style)) {
            $beforeChain = array_merge(
                $beforeChain,
                match (Style::from($specification->style)) {
                    Style::Matrix => [
                        new Matrix('array', $specification->explode ?? false),
                    ],
                    Style::Label => [
                        new Filter\String\LeftTrim('.'),
                        new Filter\String\Explode(
                            $specification->explode ?? false ?
                                '.' :
                                ','
                        ),
                    ],
                    Style::Form => [
                        new Form('array', $specification->explode ?? true),
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

        if ($specification->enum !== null) {
            $beforeChain[] = new Contained($specification->enum);
        }

        if ($specification->minItems > 0 || $specification->maxItems !== null) {
            $beforeChain[] = new Count($specification->minItems, $specification->maxItems);
        }

        if ($specification->uniqueItems === true) {
            $beforeChain[] = new Unique();
        }

        $beforeSet = new BeforeSet(...$beforeChain);

        if ($specification->items === null) {
            return new Collection($specification->fieldName, $beforeSet);
        }

        $collection = new Collection(
            $specification->fieldName,
            $beforeSet,
            $this->fromSchema(
                $specification->openAPIVersion,
                $specification->items,
                '',
                $specification->convertFromString,
            )
        );

        if ($specification->nullable) {
            return $this->handleNullable($specification->fieldName, $collection);
        }

        return $collection;
    }
}
