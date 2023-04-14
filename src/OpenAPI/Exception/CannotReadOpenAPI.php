<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Exception;

use RuntimeException;

/*
 * This exception occurs when the file specified cannot be read as OpenAPI.
 * This may be due to one of the following reasons:
 * 1: The file cannot be found at the given filepath
 * 2: The file is not recognized as OpenAPI
 */

class CannotReadOpenAPI extends RuntimeException
{
    public const FILE_NOT_FOUND = 0;
    public const INVALID_FORMAT = 1;
    public const NOT_RECOGNIZED_AS_OPEN_API = 2;

    public static function fileNotFound(string $path): self
    {
        $message = sprintf('%s not found at %s', pathinfo($path, PATHINFO_BASENAME), $path);
        return new self($message, self::FILE_NOT_FOUND);
    }

    public static function invalidFormat(string $fileExtension): self
    {
        $message = sprintf('OpenAPI must be json or yaml, %s provided', $fileExtension);
        return new self($message, self::INVALID_FORMAT);
    }

    public static function notRecognizedAsOpenAPI(string $fileName, \Throwable $e): self
    {
        $message = sprintf('%s is not recognized as OpenAPI', $fileName);
        return new self($message, self::NOT_RECOGNIZED_AS_OPEN_API, $e);
    }
}
