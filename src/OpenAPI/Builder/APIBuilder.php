<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Builder\Builder;
use Membrane\OpenAPI;
use Membrane\OpenAPI\Processor\AllOf;
use Membrane\OpenAPI\Processor\AnyOf;
use Membrane\OpenAPI\Processor\OneOf;
use Membrane\OpenAPIReader\ValueObject\Valid\{Enum\Type, V30};
use Membrane\OpenAPIReader\OpenAPIVersion;
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
        OpenAPIVersion $openAPIVersion,
        V30\Schema $schema,
        string $fieldName = '',
        bool $convertFromString = false,
        bool $convertFromArray = false,
        ?string $style = null,
        ?bool $explode = null,
    ): Processor {
        if ($schema->not !== null) {
            throw OpenAPI\Exception\CannotProcessOpenAPI::unsupportedKeyword('not');
        }

        if ($schema->allOf !== null) {
            assert(!empty($schema->allOf));
            return $this->fromComplexSchema(
                $openAPIVersion,
                AllOf::class,
                $fieldName,
                $schema->allOf,
                $convertFromString,
                $convertFromArray,
                $style,
                $explode,
            );
        }

        if ($schema->anyOf !== null) {
            assert(!empty($schema->anyOf));
            return $this->fromComplexSchema(
                $openAPIVersion,
                AnyOf::class,
                $fieldName,
                $schema->anyOf,
                $convertFromString,
                $convertFromArray,
                $style,
                $explode,
            );
        }

        if ($schema->oneOf !== null) {
            assert(!empty($schema->oneOf));
            return $this->fromComplexSchema(
                $openAPIVersion,
                OneOf::class,
                $fieldName,
                $schema->oneOf,
                $convertFromString,
                $convertFromArray,
                $style,
                $explode,
            );
        }

        return match ($schema->type) {
            Type::String => ($this->getStringBuilder())
                ->build(new OpenAPI\Specification\Strings(
                    $openAPIVersion,
                    $fieldName,
                    $schema,
                    $convertFromArray,
                    $style
                )),

            Type::Integer, Type::Number => $this->getNumericBuilder()
                ->build(new OpenAPI\Specification\Numeric(
                    $openAPIVersion,
                    $fieldName,
                    $schema,
                    $convertFromString,
                    $convertFromArray,
                    $style
                )),

            Type::Boolean => $this->getTrueFalseBuilder()
                ->build(new OpenAPI\Specification\TrueFalse(
                    $openAPIVersion,
                    $fieldName,
                    $schema,
                    $convertFromString,
                    $convertFromArray,
                    $style,
                )),

            Type::Array => $this->getArrayBuilder()
                ->build(new OpenAPI\Specification\Arrays(
                    $openAPIVersion,
                    $fieldName,
                    $schema,
                    $convertFromString,
                    $convertFromArray,
                    $style,
                    $explode,
                )),

            Type::Object => $this->getObjectBuilder()
                ->build(new OpenAPI\Specification\Objects(
                    $openAPIVersion,
                    $fieldName,
                    $schema,
                    $convertFromString,
                    $convertFromArray,
                    $style,
                    $explode,
                )),

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
     * @param non-empty-array<V30\Schema> $subSchemas
     */
    private function fromComplexSchema(
        OpenAPIVersion $openAPIVersion,
        string $complexSchemaClass,
        string $fieldName,
        array $subSchemas,
        bool $convertFromString,
        bool $convertFromArray,
        ?string $style,
        ?bool $explode,
    ): Processor {
        if (count($subSchemas) < 2) {
            return $this->fromSchema(
                $openAPIVersion,
                $subSchemas[0],
                $fieldName,
                $convertFromString,
                $convertFromArray,
            );
        }

        $subProcessors = [];
        foreach ($subSchemas as $index => $subSchema) {
            $title = null;
            if (isset($subSchema->title) && $subSchema->title !== '') {
                $title = $subSchema->title;
            }

            $subProcessors[] = $this->fromSchema(
                $openAPIVersion,
                $subSchema,
                $title ?? sprintf('Branch-%s', $index + 1),
                $convertFromString,
                $convertFromArray,
                $style,
                $explode,
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
            $this->numericBuilder = new OpenAPI\Builder\Numeric();
        }

        return $this->numericBuilder;
    }

    private function getStringBuilder(): Strings
    {
        if (!isset($this->stringBuilder)) {
            $this->stringBuilder = new OpenAPI\Builder\Strings();
        }
        return $this->stringBuilder;
    }
}
