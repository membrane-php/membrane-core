<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Exception;

use cebe\openapi\exceptions\TypeErrorException;
use RuntimeException;

class InvalidOpenAPI extends RuntimeException
{
    public const INVALID_OPEN_API = 0;
    public const INVALID_TYPE = 1;
    public const INVALID_PATH = 2;

    public static function invalidOpenAPI(string $fileName): self
    {
        $message = sprintf('%s is not valid OpenAPI', $fileName);
        return new self($message, self::INVALID_OPEN_API);
    }

    public static function invalidSpecData(TypeErrorException $e): self
    {
        $message = 'Data provided is not correct type according to OpenAPI specification';
        return new self($message, self::INVALID_TYPE, $e);
    }

    public static function invalidPath(string $path): self
    {
        $message = sprintf('%s is an invalid OpenAPI path', $path);
        return new self($message, self::INVALID_PATH);
    }
}
