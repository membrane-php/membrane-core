<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Exception;

use Membrane\OpenAPIReader\Method;
use RuntimeException;

/*
 * This exception occurs when your OpenAPI has been read and processed by Membrane
 * but your Request cannot be processed.
 * This may occur for one of the following reasons:
 * Your specification contains features currently unsupported by Membrane
 * Your specification does not match anything found in your OpenAPI spec.
 */

class CannotProcessSpecification extends RuntimeException
{
    public const PATH_MISMATCH = 0;
    public const PATH_NOT_FOUND = 1;
    public const METHOD_NOT_FOUND = 2;
    public const METHOD_NOT_SUPPORTED = 3;
    public const TYPE_MISMATCH = 4;

    public static function mismatchedPath(string $expectedPathRegex, string $requestPath): self
    {
        $message = sprintf('%s does not match expected regex: "%s"', $requestPath, $expectedPathRegex);
        return new self($message, self::PATH_MISMATCH);
    }

    public static function pathNotFound(string $fileName, string $url): self
    {
        $message = sprintf('%s does not match any specified paths in %s', $url, $fileName);
        return new self($message, self::PATH_NOT_FOUND);
    }

    public static function methodNotFound(string $method): self
    {
        $message = sprintf('%s operation not specified on path', $method);
        return new self($message, self::METHOD_NOT_FOUND);
    }

    public static function methodNotSupported(string $method): self
    {
        $supportedMethods = array_map(fn($p) => $p->value, Method::cases());

        $message = sprintf(
            "Membrane currently supports the following methods:\n\t- %s\nMethod provided: \"%s\"",
            implode("\n\t- ", $supportedMethods),
            $method
        );

        return new self($message, self::METHOD_NOT_SUPPORTED);
    }

    public static function mismatchedType(string $processor, string $expected, ?string $actual): self
    {
        $message = sprintf('%s expects %s data types, %s provided', $processor, $expected, $actual ?? 'no type');
        return new self($message, self::TYPE_MISMATCH);
    }
}
