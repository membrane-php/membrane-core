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
        $type = gettype($value);

        if ($type !== 'array') {
            $message = new Message('Value passed to IsArray validator is not an array, %s passed instead', [$type]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        if (array_is_list($value) && $value !== []) {
            $message = new Message('Value passed to IsArray validator is a list, string keys required for an array', []);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::valid($value);
    }
}
