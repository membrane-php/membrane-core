<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Exception;
use Membrane\Builder\Builder;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Builder as ParameterBuilder;
use Membrane\OpenAPI\Processor\AllOf;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Processor\OneOf;
use Membrane\OpenAPI\Specification as ParameterSpecification;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Type\IsNull;
use Membrane\Validator\Utility\Passes;

class Operation implements Builder
{
    private ParameterBuilder\Arrays $arrayBuilder;
    private ParameterBuilder\TrueFalse $trueFalseBuilder;
    private ParameterBuilder\Numeric $numericBuilder;
    private ParameterBuilder\Objects $objectBuilder;
    private ParameterBuilder\Strings $stringBuilder;

    public function supports(Specification $specification): bool
    {
        return $specification instanceof \Membrane\OpenAPI\Specification\Operation;
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof \Membrane\OpenAPI\Specification\Operation);

        $path = $query = $header = $cookie = ['required' => [], 'parameters' => []];
        $parameterProcessors = ['path' => $path, 'query' => $query, 'header' => $header, 'cookie' => $cookie];

        foreach ($specification->parameters as $parameter) {
            assert($parameter instanceof Parameter);

            //TODO move $parameter->in conditional to Parameter specification once created
            if ($parameter->required || $parameter->in === 'path') {
                $parameterProcessors[$parameter->in]['required'][] = $parameter->name;
            }

            $parameterProcessor = $this->fromSchema($this->findSchema($parameter), $parameter->name);
            $parameterProcessors[$parameter->in]['parameters'][] = $parameterProcessor;
        }

        $locationProcessors = [];
        foreach ($parameterProcessors as $location => $content) {
            $processors = $content['parameters'];
            if ($content['required'] !== []) {
                $processors[] = new Processor\BeforeSet(new RequiredFields(...$content['required']));
            }
            if ($processors !== []) {
                $locationProcessors[] = new FieldSet($location, ...$processors);
            }
        }

        return new FieldSet($specification->operationId, ...$locationProcessors);
    }

    //TODO potentially move this into Operation specification
    private function findSchema(Parameter|Reference $parameter): Schema
    {
        $schemaLocations = [];

        if ($parameter->schema !== null) {
            $schemaLocations[] = $parameter->schema;
        }

        if ($parameter->content !== []) {
            $schemaLocations[] = $parameter->content['application/json']?->schema
                ??
                throw new Exception('APISpec requires application/json content');
        }

        if (count($schemaLocations) !== 1) {
            throw new Exception('Parameters MUST contain a "schema" or "content" property, but not both');
        }

        assert($schemaLocations[0] instanceof Schema);
        return $schemaLocations[0];
    }

    private function fromSchema(Schema $schema, string $fieldName = '', bool $strict = true): Processor
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
                $specification = new ParameterSpecification\Strings($fieldName, $schema);
                return ($this->getStringsBuilder())->build($specification);
            case 'number':
            case 'integer':
                $specification = new ParameterSpecification\Numeric($fieldName, $schema, $strict);
                return $this->getNumericBuilder()->build($specification);
            case 'boolean':
                $specification = new ParameterSpecification\TrueFalse($fieldName, $schema, $strict);
                return $this->getTrueFalseBuilder()->build($specification);
            case 'array':
                $specification = new ParameterSpecification\Arrays($fieldName, $schema);
                return $this->getArrayBuilder()->build($specification);
            case 'object':
                $specification = new ParameterSpecification\Objects($fieldName, $schema);
                return $this->getObjectsBuilder()->build($specification);
            default:
                return new Field('', new Passes());
        }
    }

    private function handleNullable(string $fieldName, Processor $processor): AnyOf
    {
        return new AnyOf(
            $fieldName,
            new Field($fieldName, new IsNull()),
            $processor
        );
    }

    private function getArrayBuilder(): ParameterBuilder\Arrays
    {
        if (!isset($this->arrayBuilder)) {
            $this->arrayBuilder = new ParameterBuilder\Arrays();
        }

        return $this->arrayBuilder;
    }

    private function getTrueFalseBuilder(): ParameterBuilder\TrueFalse
    {
        if (!isset($this->trueFalseBuilder)) {
            $this->trueFalseBuilder = new ParameterBuilder\TrueFalse();
        }

        return $this->trueFalseBuilder;
    }

    private function getObjectsBuilder(): ParameterBuilder\Objects
    {
        if (!isset($this->objectBuilder)) {
            $this->objectBuilder = new ParameterBuilder\Objects();
        }

        return $this->objectBuilder;
    }

    private function getNumericBuilder(): ParameterBuilder\Numeric
    {
        if (!isset($this->numericBuilder)) {
            $this->numericBuilder = new ParameterBuilder\Numeric();
        }

        return $this->numericBuilder;
    }

    private function getStringsBuilder(): ParameterBuilder\Strings
    {
        if (!isset($this->stringBuilder)) {
            $this->stringBuilder = new ParameterBuilder\Strings();
        }
        return $this->stringBuilder;
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

        return new AllOf($fieldName, ...$fieldSets);
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

        return new AnyOf($fieldName, ...$fieldSets);
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

        return new OneOf($fieldName, ...$fieldSets);
    }
}
