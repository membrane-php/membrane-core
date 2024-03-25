<?php

declare(strict_types=1);

namespace Membrane\Filter\String;

use Membrane\Exception\InvalidFilterArguments;
use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class Implode implements Filter
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
        if (!is_array($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('Implode Filter expects an array value, %s passed instead', [gettype($value)])
                )
            );
        }

        assert($this->delimiter !== '');
        return Result::noResult(implode($this->delimiter, $value));
    }

    public function __toString()
    {
        return sprintf('implode array value taking "%s" as a delimiter', $this->delimiter);
    }

    public function __toPHP(): string
    {
        return sprintf('new %s("%s")', self::class, $this->delimiter);
    }
}
