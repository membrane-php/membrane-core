<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Exception;

use cebe\openapi\exceptions\UnresolvableReferenceException;

class CannotReadOpenAPI extends \RuntimeException
{
    public const FILE_NOT_FOUND = 0;
    public const FILE_EXTENSION_NOT_SUPPORTED = 1;
    public const FORMAT_NOT_SUPPORTED = 2;
    public const REFERENCES_NOT_RESOLVED = 3;
    public const PATH_NOT_FOUND = 4;

    public static function fileNotFound(string $path): self
    {
        $message = sprintf('%s not found at %s', pathinfo($path, PATHINFO_BASENAME), $path);
        return new self($message, self::FILE_NOT_FOUND);
    }

    public static function unsupportedFileType(string $fileExtension): self
    {
        $message = sprintf('APISpec expects json or yaml, %s provided', $fileExtension);
        return new self($message, self::FILE_EXTENSION_NOT_SUPPORTED);
    }

    public static function unsupportedFormat(string $fileName): self
    {
        $message = sprintf('%s is not following an OpenAPI format', $fileName);
        return new self($message, self::FORMAT_NOT_SUPPORTED);
    }

    public static function unresolvedReference(string $fileName, UnresolvableReferenceException $e): self
    {
        $message = sprintf('Failed to resolve references in %s', $fileName);
        return new self($message, self::REFERENCES_NOT_RESOLVED, $e);
    }

    public static function pathNotFound(string $fileName, string $url): self
    {
        $message = sprintf('%s does not match any specified paths in %s', $url, $fileName);
        return new self($message, self::PATH_NOT_FOUND);
    }
}
