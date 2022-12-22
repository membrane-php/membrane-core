<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Exception;

use cebe\openapi\exceptions\UnresolvableReferenceException;
use RuntimeException;

/*
 * This exception occurs when the file specified cannot be read as an OpenAPI document.
 * This may be due to one of the following reasons:
 * 1: The file cannot be found on the path provided
 * 2: The file is not following the OpenAPI specification
 */

class CannotReadOpenAPI extends RuntimeException
{
    public const FILE_NOT_FOUND = 0;
    public const FILE_EXTENSION_NOT_SUPPORTED = 1;
    public const FORMAT_NOT_SUPPORTED = 2;
    public const REFERENCES_NOT_RESOLVED = 3;
    public const INVALID_OPEN_API = 4;

    public static function fileNotFound(string $path): self
    {
        $message = sprintf('%s not found at %s', pathinfo($path, PATHINFO_BASENAME), $path);
        return new self($message, self::FILE_NOT_FOUND);
    }

    public static function fileTypeNotSupported(string $fileExtension): self
    {
        $message = sprintf('APISpec expects json or yaml, %s provided', $fileExtension);
        return new self($message, self::FILE_EXTENSION_NOT_SUPPORTED);
    }

    public static function cannotParse(string $fileName, \Throwable $e): self
    {
        $message = sprintf('%s is not following an OpenAPI format', $fileName);
        return new self($message, self::FORMAT_NOT_SUPPORTED, $e);
    }

    public static function invalidOpenAPI(string $fileName): self
    {
        $message = sprintf('%s is not valid OpenAPI', $fileName);
        return new self($message, self::INVALID_OPEN_API);
    }

    public static function unresolvedReference(string $fileName, UnresolvableReferenceException $e): self
    {
        $message = sprintf('Failed to resolve references in %s', $fileName);
        return new self($message, self::REFERENCES_NOT_RESOLVED, $e);
    }
}
