<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Exception;

use RuntimeException;

/*
 * This exception occurs when the OpenAPI has been read and parsed as OpenAPI
 * but Membrane cannot process it further due to user error.
 * This may occur for one of the following reasons:
 * 1: Your request contains features currently unsupported by Membrane
 * 2: Your request does not match anything found in your OpenAPI spec.
 */

class CannotProcessRequest extends RuntimeException
{
    public const PATH_NOT_FOUND = 0;
    public const METHOD_NOT_FOUND = 1;
    public const CONTENT_TYPE_NOT_SUPPORTED = 2;

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

    public static function unsupportedContent(): self
    {
        $message = sprintf('APISpec expects application/json content');
        return new self($message, self::CONTENT_TYPE_NOT_SUPPORTED);
    }
}
