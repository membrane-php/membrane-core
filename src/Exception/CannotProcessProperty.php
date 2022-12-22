<?php

declare(strict_types=1);

namespace Membrane\Exception;

use RuntimeException;

/*
 * This exception occurs if Membrane cannot process the data provided.
 * This may be due to one of the following reasons:
 * 1: Data is missing required properties
 * 2: Data contains properties that are currently unsupported
 */

class CannotProcessProperty extends RuntimeException
{
    public static function noTypeHint(string $propertyName): self
    {
        $message = sprintf('Property %s does not define it\'s type', $propertyName);
        return new self($message);
    }

    public static function compoundPropertyType(string $propertyName): self
    {
        $message = sprintf('Property %s uses a compound type hint, these are not currently supported', $propertyName);
        return new self($message);
    }

    public static function noSubtypeHint(string $propertyName): self
    {
        //'Collections (array typed properties) must define their subtype in an annotation'
        $message = sprintf('Property %s is a collection but does not define it\'s subtype', $propertyName);
        return new self($message);
    }

    public static function nestedCollection(string $propertyName): self
    {
        $message = sprintf(
            'Property %s is a collection and defines it\'s subtype as array. ' .
            'Nested collections are not currently supported',
            $propertyName
        );

        return new self($message);
    }
}
