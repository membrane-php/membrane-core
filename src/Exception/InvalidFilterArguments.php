<?php

declare(strict_types=1);

namespace Membrane\Exception;

class InvalidFilterArguments extends \RuntimeException
{
    public const EMPTY_STRING_DELIMITER = 0;

    public static function emptyStringDelimiter(): self
    {
        $message = 'Cannot use an empty string as a delimiter for explode';
        return new self($message, self::EMPTY_STRING_DELIMITER);
    }
}
