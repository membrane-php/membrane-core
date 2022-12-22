<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Exception;

use RuntimeException;

/*
 * This exception occurs if your Open API is valid and readable but your data still cannot be processed.
 * This may occur for one of the following reasons:
 * 1: Your OpenAPI spec contains features currently unsupported by Membrane
 * 2: Your OpenAPI spec does not contain the data you're trying to process.
 */

class CannotProcessOpenAPI extends RuntimeException
{
    public const INVALID_PATH_IN_OPEN_API = 0;
    public const RESPONSE_NOT_FOUND = 1;
    public const PATH_MISMATCH = 2;
    public const TYPE_MISMATCH = 3;


    public static function invalidPath(string $path): self
    {
        $message = sprintf('%s is an invalid Open API path', $path);
        return new self($message, self::INVALID_PATH_IN_OPEN_API);
    }

    public static function responseNotFound(string $httpStatus): self
    {
        $message = sprintf('No applicable response for %s http status code', $httpStatus);
        return new self($message, self::RESPONSE_NOT_FOUND);
    }

    public static function mismatchedPath(string $expectedPathRegex, string $requestPath): self
    {
        $message = sprintf('%s does not match expected regex: "%s"', $requestPath, $expectedPathRegex);
        return new self($message, self::TYPE_MISMATCH);
    }

    public static function mismatchedType(string $processor, string $expected, ?string $actual): self
    {
        $message = sprintf('%s expects %s data types, %s provided', $processor, $expected, $actual ?? 'no type');
        return new self($message, self::TYPE_MISMATCH);
    }
}
