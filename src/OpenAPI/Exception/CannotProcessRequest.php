<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Exception;

/*
 * This exception occurs if your Open API is valid and readable but your request cannot be processed.
 * This may occur for one of the following reasons:
 * 1: Your request contains features currently unsupported by Membrane
 * 2: Your request does not match anything found in your OpenAPI spec.
 */

class CannotProcessRequest extends \RuntimeException
{
    public const PATH_NOT_FOUND = 0; //404
    public const OPERATION_NOT_FOUND = 1; //405
    public const CONTENT_TYPE_NOT_SUPPORTED = 2;//406

    public static function pathNotFound(string $fileName, string $url): self
    {
        $message = sprintf('%s does not match any specified paths in %s', $url, $fileName);
        return new self($message, self::PATH_NOT_FOUND);
    }

    public static function operationNotFound(string $method): self
    {
        $message = sprintf('%s operation not specified on path', $method);
        return new self($message, self::OPERATION_NOT_FOUND);
    }

    public static function unsupportedContent(): self
    {
        $message = sprintf('APISpec expects application/json content');
        return new self($message, self::CONTENT_TYPE_NOT_SUPPORTED);
    }
}
