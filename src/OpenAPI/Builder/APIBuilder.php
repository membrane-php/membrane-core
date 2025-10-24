<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Builder\Builder;
use Membrane\OpenAPI;
use Membrane\OpenAPIReader\ValueObject\Valid\{Enum\Type, V30, V31};
use Membrane\Processor;
use Membrane\Processor\AllOf;
use Membrane\Processor\AnyOf;
use Membrane\Processor\Field;
use Membrane\Processor\OneOf;
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
        V30\Schema|V31\Schema $schema,
        string $fieldName = '',
        bool $convertFromString = false,
        bool $convertFromArray = false,
        ?string $style = null,
        ?bool $explode = null,
    ): Processor {
        if (is_bool($schema->value)) {
            return new Field($fieldName, $schema->value ?
                new Utility\Passes() :
                new Utility\Fails());
        }

        $result = [];

        if ($schema->value->not->value !== false) {
            throw OpenAPI\Exception\CannotProcessOpenAPI::unsupportedKeyword('not');
        }

        if (!empty($schema->value->allOf)) {
            $result[] = $this->fromComplexSchema(
                AllOf::class,
                $fieldName,
                $schema->value->allOf,
                $convertFromString,
                $convertFromArray,
                $style,
                $explode,
            );
        }

        if (!empty($schema->value->anyOf)) {
            $result[] = $this->fromComplexSchema(
                AnyOf::class,
                $fieldName,
                $schema->value->anyOf,
                $convertFromString,
                $convertFromArray,
                $style,
                $explode,
            );
        }

        if (!empty($schema->value->oneOf)) {
            $result[] = $this->fromComplexSchema(
                OneOf::class,
                $fieldName,
                $schema->value->oneOf,
                $convertFromString,
                $convertFromArray,
                $style,
                $explode,
            );
        }

        $typeSpecificProcessors = array_map(
            fn ($t) => match ($t) {
                Type::Array => $this->getArrayBuilder()
                    ->build(new OpenAPI\Specification\Arrays(
                        $fieldName,
                        $schema->value,
                        $convertFromString,
                        $convertFromArray,
                        $style,
                        $explode,
                    )),

                Type::Boolean => $this->getTrueFalseBuilder()
                    ->build(new OpenAPI\Specification\TrueFalse(
                        $fieldName,
                        $schema->value,
                        $convertFromString,
                        $convertFromArray,
                        $style,
                    )),

                Type::Integer, Type::Number => $this->getNumericBuilder()
                    ->build(new OpenAPI\Specification\Numeric(
                        $fieldName,
                        $schema->value,
                        $convertFromString,
                        $convertFromArray,
                        $style
                    )),

                Type::String => ($this->getStringBuilder())
                    ->build(new OpenAPI\Specification\Strings(
                        $fieldName,
                        $schema->value,
                        $convertFromArray,
                        $style
                    )),

                Type::Object => $this->getObjectBuilder()
                    ->build(new OpenAPI\Specification\Objects(
                        $fieldName,
                        $schema->value,
                        $convertFromString,
                        $convertFromArray,
                        $style,
                        $explode,
                    )),

                Type::Null => new Field($fieldName, new IsNull()),
            },
            $schema->value->types,
        );

        if (count($typeSpecificProcessors) >= 2) {
            $result[] = new AnyOf($fieldName, ...$typeSpecificProcessors);
        } elseif (count($typeSpecificProcessors) === 1) {
            $result[] = $typeSpecificProcessors[0];
        }

        if (count($result) >= 2) {
            return new AllOf($fieldName, ...$result);
        } elseif (count($result) === 1) {
            return $result[0];
        } else {
            return new Field($fieldName, new Utility\Passes());
        }
    }

    /**
     * @param class-string<AllOf|AnyOf|OneOf> $complexSchemaClass
     * @param non-empty-list<V30\Schema|V31\Schema> $subSchemas
     */
    private function fromComplexSchema(
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
                $subSchemas[0],
                $fieldName,
                $convertFromString,
                $convertFromArray,
            );
        }

        $subProcessors = [];
        foreach ($subSchemas as $index => $subSchema) {
            $title = null;
            if (isset($subSchema->value->title) && $subSchema->value->title !== '') {
                $title = $subSchema->value->title;
            }

            $subProcessors[] = $this->fromSchema(
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
