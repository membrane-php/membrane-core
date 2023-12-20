<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use cebe\openapi\spec\Schema;
use Membrane\Builder\Specification;
use Membrane\Filter\Shape\KeyValueSplit;
use Membrane\Filter\String\Explode;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\DefaultProcessor;
use Membrane\Processor\FieldSet;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\FieldSet\FixedFields;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Type\IsArray;

class Objects extends APIBuilder
{
    private const STYLE_FORM = 'form';
    private const STYLE_SPACE_DELIMITED = 'spaceDelimited';
    private const STYLE_PIPE_DELIMITED = 'pipeDelimited';
    private const STYLE_DEEP_OBJECT = 'deepObject';
    private const STYLE_DELIMITER_MAP = [
        self::STYLE_FORM => ',',
        self::STYLE_SPACE_DELIMITED => ' ',
        self::STYLE_PIPE_DELIMITED => '|',
    ];

    public function supports(Specification $specification): bool
    {
        return $specification instanceof \Membrane\OpenAPI\Specification\Objects;
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof \Membrane\OpenAPI\Specification\Objects);

        $beforeChain = [];

        if (isset($specification->style)) {
            switch ($specification->style) {
                case self::STYLE_FORM:
                case self::STYLE_SPACE_DELIMITED:
                case self::STYLE_PIPE_DELIMITED:
                    $beforeChain[] = new Explode(self::STYLE_DELIMITER_MAP[$specification->style]);
                    $beforeChain[] = new KeyValueSplit();
                    break;
                case self::STYLE_DEEP_OBJECT:
                    // parse_str from HTTPParameters already deals with this
                    break;
            }
        }

        $beforeChain = [new IsArray()];

        if ($specification->enum !== null) {
            $beforeChain[] = new Contained($specification->enum);
        }

        if ($specification->required !== null) {
            $beforeChain[] = new RequiredFields(...$specification->required);
        }

        if ($specification->additionalProperties === false) {
            $beforeChain[] = new FixedFields(...array_keys($specification->properties));
        }
        // @TODO support minProperties and maxProperties

        $beforeSet = new BeforeSet(...$beforeChain);

        $fields = [];

        foreach ($specification->properties as $key => $schema) {
            assert($schema instanceof Schema);
            $fields [] = $this->fromSchema($schema, $key);
        }

        if ($specification->additionalProperties instanceof Schema) {
            $fields [] = new DefaultProcessor(
                $this->fromSchema($specification->additionalProperties)
            );
        }

        $processor = new FieldSet($specification->fieldName, $beforeSet, ...$fields);

        if ($specification->nullable) {
            return $this->handleNullable($specification->fieldName, $processor);
        }

        return $processor;
    }
}
