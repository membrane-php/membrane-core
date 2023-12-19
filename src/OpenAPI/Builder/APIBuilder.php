<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Membrane\Builder\Builder;
use Membrane\OpenAPI;
use Membrane\OpenAPI\Processor\AllOf;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Processor\OneOf;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Validator\Type\IsNull;
use Membrane\Validator\Utility;

abstract class APIBuilder implements Builder
{
    private Arrays $arrayBuilder;
    private TrueFalse $trueFalseBuilder;
    private Numeric $numericBuilder;
    private Objects $objectBuilder;
    private Strings $stringBuilder;

    public function fromSchema(
        Schema $schema,
        string $fieldName = '',
        bool $fromString = false,
        bool $fromArray = false,
        ?string $style = null,
    ): Processor {
        if ($schema->not !== null) {
            throw OpenAPI\Exception\CannotProcessOpenAPI::unsupportedKeyword('not');
        }

        if ($schema->allOf !== null) {
            return $this->fromComplexSchema(
                AllOf::class,
                $fieldName,
                $schema->allOf,
                $fromString
            );
        }

        if ($schema->anyOf !== null) {
            return $this->fromComplexSchema(
                AnyOf::class,
                $fieldName,
                $schema->anyOf,
                $fromString
            );
        }

        if ($schema->oneOf !== null) {
            return $this->fromComplexSchema(
                OneOf::class,
                $fieldName,
                $schema->oneOf,
                $fromString
            );
        }

        return match ($schema->type) {
            'string' => ($this->getStringBuilder())
                ->build(new OpenAPI\Specification\Strings($fieldName, $schema, $fromArray)),

            'number', 'integer' => $this->getNumericBuilder()
                ->build(new OpenAPI\Specification\Numeric($fieldName, $schema, $fromString, $fromArray)),

            'boolean' => $this->getTrueFalseBuilder()
                ->build(
                    new OpenAPI\Specification\TrueFalse($fieldName, $schema, $fromString, $fromArray)
                ),

            'array' => $this->getArrayBuilder()
                ->build(new OpenAPI\Specification\Arrays($fieldName, $schema, $fromString, $style)),

            //todo objects do not work in headers
            'object' => $this->getObjectBuilder()
                ->build(new OpenAPI\Specification\Objects($fieldName, $schema, $style)),

            default => new Field('', new Utility\Passes()),
        };
    }

    protected function handleNullable(string $fieldName, Processor $processor): AnyOf
    {
        return new AnyOf(
            $fieldName,
            new Field($fieldName, new IsNull()),
            $processor
        );
    }

    /**
     * @param class-string<AllOf|AnyOf|OneOf> $complexSchemaClass
     * @param Reference[]|Schema[] $subSchemas
     */
    private function fromComplexSchema(
        string $complexSchemaClass,
        string $fieldName,
        array $subSchemas,
        bool $convertFromString
    ): Processor {
        if (empty($subSchemas)) {
            throw OpenAPI\Exception\CannotProcessOpenAPI::pointlessComplexSchema($fieldName);
        }

        if (count($subSchemas) < 2) {
            assert($subSchemas[0] instanceof Schema);
            return $this->fromSchema($subSchemas[0], $fieldName, $convertFromString);
        }

        $subProcessors = [];

        foreach ($subSchemas as $index => $subSchema) {
            assert($subSchema instanceof Schema);

            $title = null;
            if (isset($subSchema->title) && $subSchema->title !== '') {
                $title = $subSchema->title;
            }

            $subProcessors[] = $this->fromSchema(
                $subSchema,
                $title ?? sprintf('Branch-%s', $index + 1),
                $convertFromString
            );
        }

        return new $complexSchemaClass($fieldName, ...$subProcessors);
    }

    private function getArrayBuilder(): Arrays
    {
        if (!isset($this->arrayBuilder)) {
            $this->arrayBuilder = new Arrays();
        }

        return $this->arrayBuilder;
    }

    private function getTrueFalseBuilder(): TrueFalse
    {
        if (!isset($this->trueFalseBuilder)) {
            $this->trueFalseBuilder = new TrueFalse();
        }

        return $this->trueFalseBuilder;
    }

    private function getObjectBuilder(): Objects
    {
        if (!isset($this->objectBuilder)) {
            $this->objectBuilder = new Objects();
        }

        return $this->objectBuilder;
    }

    private function getNumericBuilder(): Numeric
    {
        if (!isset($this->numericBuilder)) {
            $this->numericBuilder = new Numeric();
        }

        return $this->numericBuilder;
    }

    private function getStringBuilder(): Strings
    {
        if (!isset($this->stringBuilder)) {
            $this->stringBuilder = new Strings();
        }
        return $this->stringBuilder;
    }
}
