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

    public function validate(mixed $value): Result
    {
        $result = preg_match($this->pattern, $value);

        if (!$result) {
            $message = new Message('String does not match the required pattern %s', [$this->pattern]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::valid($value);
    }
}
