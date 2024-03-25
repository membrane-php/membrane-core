<?php

declare(strict_types=1);

namespace Membrane\Validator\String;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class IntString implements Validator
{
    public function __toString(): string
    {
        return 'is a string of an integer';
    }

    public function __toPHP(): string
    {
        return sprintf('new %s()', self::class);
    }

    public function validate(mixed $value): Result
    {
        if (!is_string($value)) {
            $message = new Message('String value expected, %s provided.', [gettype($value)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        if (!is_numeric($value) || $value !== (string)(int)$value) {
            $message = new Message('String value must be an integer.', []);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::valid($value);
    }
}
