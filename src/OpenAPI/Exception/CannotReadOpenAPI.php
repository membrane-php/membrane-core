<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Exception;

use cebe\openapi\exceptions\UnresolvableReferenceException;

class CannotReadOpenAPI extends \RuntimeException
{
    public const FILE_NOT_FOUND = 0;
    public const FILE_EXTENSION_NOT_SUPPORTED = 1;
    public const FORMAT_NOT_SUPPORTED = 2;
    public const CONTENT_NOT_SUPPORTED = 3;
    public const REFERENCES_NOT_RESOLVED = 4;
    public const PATH_NOT_FOUND = 5;
    public const OPERATION_NOT_FOUND = 6;
    public const RESPONSE_NOT_FOUND = 7;

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

    public static function unsupportedContent(): self
    {
        $message = sprintf('APISpec expects application/json content');
        return new self($message, self::CONTENT_NOT_SUPPORTED);
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

    public static function operationNotFound(string $method): self
    {
        $message = sprintf('%s operation not specified on path', $method);
        return new self($message, self::OPERATION_NOT_FOUND);
    }

    public static function responseNotFound(string $httpStatus): self
    {
        $message = sprintf('No applicable response for %s http status code', $httpStatus);
        return new self($message, self::RESPONSE_NOT_FOUND);
    }
}
