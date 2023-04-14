<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Exception;

class CannotProcessResponse extends \RuntimeException
{
    public const CODE_NOT_FOUND = 0;

    public static function codeNotFound(string $httpStatusCode): self
    {
        $message = sprintf('No applicable response for %s http status code', $httpStatusCode);
        return new self($message, self::CODE_NOT_FOUND);
    }
}
