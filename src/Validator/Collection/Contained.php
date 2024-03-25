<?php

declare(strict_types=1);

namespace Membrane\Validator\Collection;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class Contained implements Validator
{
    /** @param mixed[] $enum */
    public function __construct(
        private readonly array $enum
    ) {
    }

    public function __toString(): string
    {
        if ($this->enum === []) {
            return 'will return invalid';
        }

        return sprintf('is one of the following values: ') .
            implode(', ', array_map(fn($p) => json_encode($p), $this->enum));
    }

    public function __toPHP(): string
    {
        return sprintf('new %s(%s)', self::class, json_encode($this->enum));
    }

    public function validate(mixed $value): Result
    {
        if (!in_array($value, $this->enum, true)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('Contained validator did not find value within array', [json_encode($this->enum)])
                )
            );
        }

        return Result::valid($value);
    }
}
