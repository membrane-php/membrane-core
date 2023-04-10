<?php

declare(strict_types=1);

namespace Membrane\Filter\String;

use Membrane\Exception\InvalidFilterArguments;
use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class Explode implements Filter
{
    public function __construct(
        private readonly string $delimiter
    ) {
        if ($this->delimiter === '') {
            throw InvalidFilterArguments::emptyStringDelimiter();
        }
    }

    public function filter(mixed $value): Result
    {
        if (!is_string($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('Explode Filter expects a string value, %s passed instead', [gettype($value)])
                )
            );
        }

        assert($this->delimiter !== '');
        return Result::noResult(explode($this->delimiter, $value));
    }

    public function __toString()
    {
        return sprintf('explode string value using "%s" as a delimiter', $this->delimiter);
    }

    public function __toPHP(): string
    {
        return sprintf('new %s("%s")', self::class, $this->delimiter);
    }
}
