<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\TempHelpers;

use cebe\openapi\spec as Cebe;
use Membrane\OpenAPIReader\Factory;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Value;
use RuntimeException;
use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\V30;

final class CreatesSchema
{
    public static function create(
        OpenAPIVersion $openAPIVersion,
        string $fieldName,
        Cebe\Schema $schema
    ): V30\Schema {
        return match ($openAPIVersion) {
            OpenAPIVersion::Version_3_0 => new V30\Schema(
                new Identifier($fieldName),
                self::createSchema($schema) ??
                    throw new RuntimeException('could not make schema'),
            ),
            OpenAPIVersion::Version_3_1 => throw new RuntimeException('WIP'),
        };
    }

    private static function createSchema(
        Cebe\Schema|Cebe\Reference|null $schema,
    ): Partial\Schema|null {
        assert(!$schema instanceof Cebe\Reference);

        if ($schema === null) {
            return null;
        }

        $createSchemas = fn($schemas) => array_filter(
            array_map(fn($s) => self::createSchema($s), $schemas),
            fn($s) => $s !== null,
        );

        return new Partial\Schema(
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
}
