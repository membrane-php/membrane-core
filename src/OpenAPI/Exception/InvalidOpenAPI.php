<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Exception;

use RuntimeException;

class InvalidOpenAPI extends RuntimeException
{
    public static function invalidPath(string $path): self
    {
        $message = sprintf('%s is an invalid OpenAPI path', $path);
        return new self($message);
    }
}
