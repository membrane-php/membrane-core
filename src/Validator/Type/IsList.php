<?php

namespace Membrane\Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class IsList implements Validator
{
    public function validate(mixed $value): Result
    {
        if (!is_array($value)) {
            $message = new Message(
                'Value passed to IsList validator is not an array, %s passed instead',
                [gettype($value)]
            );
            return Result::invalid($value, new MessageSet(null, $message));
        }

        if (!array_is_list($value)) {
            $message = new Message('Value passed to IsList validator is an array, lists do not have keys', []);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::valid($value);
    }
}
