<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use cebe\openapi\spec\Schema;
use Membrane\Builder\Specification;
use Membrane\Filter;
use Membrane\OpenAPI\Filter\FormatStyle\DeepObject;
use Membrane\OpenAPI\Filter\FormatStyle\Form;
use Membrane\OpenAPI\Filter\FormatStyle\Matrix;
use Membrane\OpenAPI\Filter\FormatStyle\PipeDelimited;
use Membrane\OpenAPI\Filter\FormatStyle\SpaceDelimited;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\DefaultProcessor;
use Membrane\Processor\FieldSet;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\Collection\Count;
use Membrane\Validator\FieldSet\FixedFields;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Type\IsArray;

class Objects extends APIBuilder
{
    public function supports(Specification $specification): bool
    {
        return $specification instanceof \Membrane\OpenAPI\Specification\Objects;
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof \Membrane\OpenAPI\Specification\Objects);

        $beforeChain = [];

        if ($specification->convertFromArray) {
            array_unshift($beforeChain, new Filter\String\Implode(','));
        }

        if (isset($specification->style)) {
            $beforeChain = array_merge(
                $beforeChain,
                match (Style::tryFrom($specification->style)) {
                    Style::Matrix => [
                        new Matrix('object', $specification->explode ?? false),
                    ],
                    Style::Label => [
                        new Filter\String\LeftTrim('.'),
                        $specification->explode ?? false ?
                            new Filter\String\Tokenize('.=') :
                            new Filter\String\Explode(','),
                    ],
                    Style::Simple => [
                        $specification->explode === true ?
                            new Filter\String\Tokenize(',=') :
                            new Filter\String\Explode(','),
                    ],
                    Style::Form => [
                        new Form('object', $specification->explode ?? true),
                    ],
                    Style::SpaceDelimited => [new SpaceDelimited()],
                    Style::PipeDelimited => [new PipeDelimited()],
                    Style::DeepObject => [new DeepObject()],
                    default => [],
                },
                [new Filter\Shape\KeyValueSplit()]
            );
        }

        $beforeChain[] = new IsArray();

        if ($specification->enum !== null) {
            $beforeChain[] = new Contained($specification->enum);
        }

        if ($specification->required !== null) {
            $beforeChain[] = new RequiredFields(...$specification->required);
        }

        if ($specification->additionalProperties === false) {
            $beforeChain[] = new FixedFields(...array_keys($specification->properties));
        }

        if ($specification->minProperties > 0 || isset($specification->maxProperties)) {
            $beforeChain[] = new Count($specification->minProperties, $specification->maxProperties);
        }

        $beforeSet = new BeforeSet(...$beforeChain);

        $fields = [];

        foreach ($specification->properties as $key => $schema) {
            assert($schema instanceof Schema);
            $fields [] = $this->fromSchema(
                $specification->openApiVersion,
                $schema,
                $key,
                $specification->convertFromString
            );
        }

        if ($specification->additionalProperties instanceof Schema) {
            $fields [] = new DefaultProcessor(
                $this->fromSchema(
                    $specification->openApiVersion,
                    $specification->additionalProperties,
                    '',
                    $specification->convertFromString
                )
            );
        }

        $processor = new FieldSet($specification->fieldName, $beforeSet, ...$fields);

        if ($specification->nullable) {
            return $this->handleNullable($specification->fieldName, $processor);
        }

        return $processor;
    }
}
