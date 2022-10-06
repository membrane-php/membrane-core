<?php

namespace Membrane\Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class IsArray implements Validator
{
    public function validate(mixed $value): Result
    {
        if (!is_array($value)) {
            $message = new Message(
                'Value passed to IsArray validator is not an array, %s passed instead',
                [gettype($value)]
            );
            return Result::invalid($value, new MessageSet(null, $message));
        }

        if (array_is_list($value) && $value !== []) {
            $message = new Message('Value passed to IsArray validator is a list, arrays have keys', []);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::valid($value);
    }
}
