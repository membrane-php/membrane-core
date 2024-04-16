<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use cebe\openapi\spec\Schema;
use Membrane\Builder\Specification;
use Membrane\Filter;
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
    private const STYLE_FORM = 'form';
    private const STYLE_SPACE_DELIMITED = 'spaceDelimited';
    private const STYLE_PIPE_DELIMITED = 'pipeDelimited';
    private const STYLE_DELIMITER_MAP = [
        self::STYLE_FORM => ',',
        self::STYLE_SPACE_DELIMITED => ' ',
        self::STYLE_PIPE_DELIMITED => '|',
    ];

    public function supports(Specification $specification): bool
    {
        return $specification instanceof \Membrane\OpenAPI\Specification\Arrays;
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof \Membrane\OpenAPI\Specification\Arrays);

        $beforeChain = [];

        if ($specification->convertFromArray) {
            array_unshift($beforeChain, new Filter\String\Implode(','));
        }

        if (isset($specification->style)) {
            switch (Style::tryFrom($specification->style)) {
                case Style::Simple:
                    $beforeChain[] = new Filter\String\Explode(',');
            }

            switch ($specification->style) {
                case self::STYLE_FORM:
                case self::STYLE_SPACE_DELIMITED:
                case self::STYLE_PIPE_DELIMITED:
                    $beforeChain[] = new Filter\String\Explode(self::STYLE_DELIMITER_MAP[$specification->style]);
                    break;
            }
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

        assert($specification->items instanceof Schema);
        $collection = new Collection(
            $specification->fieldName,
            $beforeSet,
            $this->fromSchema(
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
