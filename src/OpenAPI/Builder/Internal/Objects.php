<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder\Internal;

use Membrane\Filter;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\Filter\FormatStyle\DeepObject;
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
use Membrane\Processor\DefaultProcessor;
use Membrane\Processor\FieldSet;
use Membrane\Validator\Collection\Contained;
use Membrane\Validator\Collection\Count;
use Membrane\Validator\FieldSet\FixedFields;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Type\IsArray;

/**
 * @internal see README.md
 */
class Objects extends APIBuilder
{
    public function build(
        string $fieldName,
        V30\Keywords | V31\Keywords $keywords,
        bool $convertFromString = false,
        bool $convertFromArray = false,
        ?string $style = null,
        ?bool $explode = null,
    ): Processor {
        if (!in_array(Type::Object, $keywords->types)) {
            throw CannotProcessSpecification::mismatchedType(
                ['object'],
                array_map(fn($t) => $t->value, $keywords->types),
            );
        }

        $beforeChain = [];

        if ($convertFromArray) {
            array_unshift($beforeChain, new Filter\String\Implode(','));
        }

        if (isset($style)) {
            $beforeChain = array_merge(
                $beforeChain,
                match (Style::tryFrom($style)) {
                    Style::Matrix => [
                        new Matrix('object', $explode ?? false),
                    ],
                    Style::Label => [
                        new Filter\String\LeftTrim('.'),
                        $explode ?? false ?
                            new Filter\String\Tokenize('.=') :
                            new Filter\String\Explode(','),
                    ],
                    Style::Simple => [
                        $explode === true ?
                            new Filter\String\Tokenize(',=') :
                            new Filter\String\Explode(','),
                    ],
                    Style::Form => [
                        new Form('object', $explode ?? true),
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

        if (
            $keywords->enum !== null
        ) {
            $beforeChain[] = new Contained(array_map(
                fn(Value $v) => $v->value,
                $keywords->enum,
            ));
        }

        if (!empty($keywords->required)) {
            $beforeChain[] = new RequiredFields(
                ...$keywords->required
            );
        }

        if ($keywords->additionalProperties->value === false) {
            $beforeChain[] = new FixedFields(
                ...array_keys($keywords->properties)
            );
        }

        if (
            $keywords->minProperties > 0
            || $keywords->maxProperties !== null
        ) {
            $beforeChain[] = new Count(
                $keywords->minProperties,
                $keywords->maxProperties,
            );
        }

        $beforeSet = new BeforeSet(...$beforeChain);

        $fields = [];

        foreach ($keywords->properties as $key => $schema) {
            $fields [] = $this->fromSchema(
                $schema,
                $key,
                $convertFromString,
            );
        }

        if (!is_bool($keywords->additionalProperties->value)) {
            $fields [] = new DefaultProcessor(
                $this->fromSchema(
                    $keywords->additionalProperties,
                    '',
                    $convertFromString,
                )
            );
        }

        return new FieldSet($fieldName, $beforeSet, ...$fields);
    }
}
