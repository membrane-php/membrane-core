<?php

declare(strict_types=1);

namespace Membrane\Filter\String;

use Membrane\Exception\InvalidFilterArguments;
use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class Tokenize implements Filter
{
    public function __construct(
        private readonly string $delimiters,
    ) {
        if ($this->delimiters === '') {
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
                    new Message('Tokenize Filter expects string, %s given', [gettype($value)])
                )
            );
        }

        $tokens = [];
        $token = strtok($value, $this->delimiters);
        while ($token !== false) {
            $tokens[] = $token;
            $token = strtok($this->delimiters);
        }

        return Result::noResult(array_filter($tokens, fn($t) => $t !== false));
    }

    public function __toString()
    {
        return sprintf(
            'Tokenize string using "%s" as a delimiter',
            $this->delimiters
        );
    }

    public function __toPHP(): string
    {
        return sprintf('new %s("%s")', self::class, $this->delimiters);
    }
}
