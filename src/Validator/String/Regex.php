<?php

declare(strict_types=1);

namespace Membrane\Validator\String;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class Regex implements Validator
{
    public function __construct(private readonly string $pattern)
    {
    }

    public function __toString(): string
    {
        return sprintf('matches the regex: "%s"', $this->pattern);
    }

    public function __toPHP(): string
    {
        return sprintf('new %s(\'%s\')', self::class, addslashes($this->pattern));
    }

    public function validate(mixed $value): Result
    {
        if (!is_string($value)) {
            $message = new Message('Regex Validator requires a string, %s given', [gettype($value)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        if (!preg_match($this->pattern, $value)) {
            $message = new Message('String does not match the required pattern %s', [$this->pattern]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::valid($value);
    }
}
