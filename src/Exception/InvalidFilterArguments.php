<?php

declare(strict_types=1);

namespace Membrane\Exception;

class InvalidFilterArguments extends \RuntimeException
{
    public const METHOD_NOT_CALLABLE = 0;
    public const EMPTY_STRING_DELIMITER = 1;

    public static function methodNotCallable(string $class, string $method): self
    {
        return new self("$class::$method must be callable", self::METHOD_NOT_CALLABLE);
    }

    public static function emptyStringDelimiter(): self
    {
        $message = 'Cannot use an empty string as a delimiter for explode';
        return new self($message, self::EMPTY_STRING_DELIMITER);
    }
}
