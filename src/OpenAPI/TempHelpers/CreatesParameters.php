<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\TempHelpers;

use cebe\openapi\spec as Cebe;
use Membrane\OpenAPIReader\Factory;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Partial\MediaType;
use Membrane\OpenAPIReader\ValueObject\Partial\Parameter;
use Membrane\OpenAPIReader\ValueObject\Partial\Schema;
use Membrane\OpenAPIReader\ValueObject\Valid\V30;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Value;
use RuntimeException;

final class CreatesParameters
{
    /**
     * @param Cebe\Parameter[]|Cebe\Reference[] $parameters
     * @return V30\Parameter[]
     */
    public static function create(
        OpenAPIVersion $openAPIVersion,
        array $parameters
    ): array {
        return match ($openAPIVersion) {
            OpenAPIVersion::Version_3_0 => array_map(
                fn($p) => new V30\Parameter(new Identifier($p->name ?? ''), $p),
                self::createParameters($parameters),
            ),
            OpenAPIVersion::Version_3_1 => throw new RuntimeException('WIP'),
        };
    }

    private static function createParameters(array $parameters): array
    {
        $result = [];

        foreach ($parameters as $parameter) {
            assert(!$parameter instanceof Cebe\Reference);

            $result[] = new Parameter(
                $parameter->name,
                $parameter->in,
                $parameter->required,
                $parameter->style,
                $parameter->explode,
                self::createSchema($parameter->schema),
                self::createContent($parameter->content),
            );
        }

        return $result;
    }

    private static function createSchema(
        Cebe\Reference|Cebe\Schema|null $schema
    ): ?Schema {
        assert(!$schema instanceof Cebe\Reference);

        if ($schema === null) {
            return null;
        }

        $createSchemas = fn($schemas) => array_filter(
            array_map(fn($s) => self::createSchema($s), $schemas),
            fn($s) => $s !== null,
        );

        return new Schema(
            type: $schema->type,
            enum: isset($schema->enum) ?
                array_map(fn($e) => new Value($e), $schema->enum) :
                null,
            default: isset($schema->default) ? new Value($schema->default) : null,
            nullable: $schema->nullable ?? false,
            multipleOf: $schema->multipleOf ?? null,
            exclusiveMaximum: $schema->exclusiveMaximum ?? false,
            exclusiveMinimum: $schema->exclusiveMinimum ?? false,
            maximum: $schema->maximum ?? null,
            minimum: $schema->minimum ?? null,
            maxLength: $schema->maxLength ?? null,
            minLength: $schema->minLength ?? 0,
            pattern: $schema->pattern ?? null,
            maxItems: $schema->maxItems ?? null,
            minItems: $schema->minItems ?? 0,
            uniqueItems: $schema->uniqueItems ?? false,
            maxProperties: $schema->maxProperties ?? null,
            minProperties: $schema->minProperties ?? 0,
            required: $schema->required ?? null,
            allOf: isset($schema->allOf) ? $createSchemas($schema->allOf) : null,
            anyOf: isset($schema->anyOf) ? $createSchemas($schema->anyOf) : null,
            oneOf: isset($schema->oneOf) ? $createSchemas($schema->oneOf) : null,
            not: isset($schema->not) ? self::createSchema($schema->not) : null,
            items: isset($schema->items) ? self::createSchema($schema->items) : null,
            properties: isset($schema->properties) ? $createSchemas($schema->properties) : [],
            additionalProperties: isset($schema->additionalProperties) ? (is_bool($schema->additionalProperties) ?
                $schema->additionalProperties :
                self::createSchema($schema->additionalProperties) ?? true) :
                true,
            format: $schema->format ?? null,
            title: $schema->title ?? null,
            description: $schema->description ?? null,
        );
    }

    /**
     * @param Cebe\MediaType[] $mediaTypes
     * @return MediaType[]
     */
    private static function createContent(array $mediaTypes): array
    {
        $result = [];

        foreach ($mediaTypes as $mediaType => $mediaTypeObject) {
            assert(!$mediaTypeObject->schema instanceof Cebe\Reference);

            $result[] = new MediaType(
                is_string($mediaType) ? $mediaType : null,
                !is_null($mediaTypeObject->schema) ?
                    self::createSchema($mediaTypeObject->schema) :
                    null
            );
        }

        return $result;
    }
}
