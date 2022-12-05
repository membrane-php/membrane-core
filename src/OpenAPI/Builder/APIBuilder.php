<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Exception;
use Membrane\Builder\Builder;
use Membrane\OpenAPI;
use Membrane\OpenAPI\Processor\AnyOf;
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

    protected function fromSchema(Schema $schema, string $fieldName = '', bool $strict = true): Processor
    {
        if ($schema->not !== null) {
            throw new Exception("Keyword 'not' is currently unsupported");
        }

        if ($schema->allOf !== null) {
            return $this->handleAllOf($schema->allOf, $fieldName);
        }

        if ($schema->anyOf !== null) {
            return $this->handleAnyOf($schema->anyOf, $fieldName);
        }

        if ($schema->oneOf !== null) {
            return $this->handleOneOf($schema->oneOf, $fieldName);
        }

        switch ($schema->type) {
            case 'string':
                $specification = new OpenAPI\Specification\Strings($fieldName, $schema);
                return ($this->getStringBuilder())->build($specification);
            case 'number':
            case 'integer':
                $specification = new OpenAPI\Specification\Numeric($fieldName, $schema, $strict);
                return $this->getNumericBuilder()->build($specification);
            case 'boolean':
                $specification = new OpenAPI\Specification\TrueFalse($fieldName, $schema, $strict);
                return $this->getTrueFalseBuilder()->build($specification);
            case 'array':
                $specification = new OpenAPI\Specification\Arrays($fieldName, $schema);
                return $this->getArrayBuilder()->build($specification);
            case 'object':
                $specification = new OpenAPI\Specification\Objects($fieldName, $schema);
                return $this->getObjectBuilder()->build($specification);
            default:
                return new Field('', new Utility\Passes());
        }
    }

    protected function handleNullable(string $fieldName, Processor $processor): AnyOf
    {
        return new AnyOf(
            $fieldName,
            new Field($fieldName, new IsNull()),
            $processor
        );
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
            $this->numericBuilder = new OpenAPI\Builder\Numeric();
        }

        return $this->numericBuilder;
    }

    /** @param Reference[]|Schema[] $allOf */
    private function handleAllOf(array $allOf, string $fieldName): Processor
    {
        if (count($allOf) < 2) {
            assert($allOf[0] instanceof Schema);
            return $this->fromSchema($allOf[0], $fieldName);
        }

        $fieldSets = [];

        // @TODO add key to messages in a useful format
        foreach ($allOf as $key => $objectSchema) {
            assert($objectSchema instanceof Schema);
            $fieldSets[] = $this->fromSchema($objectSchema, $fieldName);
        }

        return new OpenAPI\Processor\AllOf($fieldName, ...$fieldSets);
    }

    /** @param Reference[]|Schema[] $anyOf */
    private function handleAnyOf(array $anyOf, string $fieldName): Processor
    {
        if (count($anyOf) < 2) {
            assert($anyOf[0] instanceof Schema);
            return $this->fromSchema($anyOf[0], $fieldName);
        }

        $fieldSets = [];

        // @TODO add key to messages in a useful format
        foreach ($anyOf as $objectSchema) {
            assert($objectSchema instanceof Schema);
            $fieldSets[] = $this->fromSchema($objectSchema, $fieldName);
        }

        return new OpenAPI\Processor\AnyOf($fieldName, ...$fieldSets);
    }

    /** @param Reference[]|Schema[] $oneOf */
    private function handleOneOf(array $oneOf, string $fieldName): Processor
    {
        if (count($oneOf) < 2) {
            assert($oneOf[0] instanceof Schema);
            return $this->fromSchema($oneOf[0], $fieldName);
        }

        $fieldSets = [];

        // @TODO add key to messages in a useful format
        foreach ($oneOf as $objectSchema) {
            assert($objectSchema instanceof Schema);
            $fieldSets[] = $this->fromSchema($objectSchema, $fieldName);
        }

        return new OpenAPI\Processor\OneOf($fieldName, ...$fieldSets);
    }

    private function getStringBuilder(): Strings
    {
        if (!isset($this->stringBuilder)) {
            $this->stringBuilder = new OpenAPI\Builder\Strings();
        }
        return $this->stringBuilder;
    }
}
