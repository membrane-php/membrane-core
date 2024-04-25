<?php

declare(strict_types=1);

namespace Membrane\Filter\String;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class LeftTrim implements Filter
{
    public function __construct(
        private readonly string $characters
    ) {
    }

    public function filter(mixed $value): Result
    {
        if (!is_string($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('LeftTrim Filter expects string value, %s given', [gettype($value)])
                )
            );
        }

        return Result::noResult(ltrim($value, $this->characters));
    }

    public function __toString(): string
    {
        return sprintf(
            'Trim "%s" off the left side of the string value',
            $this->characters,
        );
    }

    public function __toPHP(): string
    {
        return sprintf('new %s("%s")', self::class, $this->characters);
    }
}
