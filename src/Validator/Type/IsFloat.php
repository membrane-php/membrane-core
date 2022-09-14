<?php

declare(strict_types=1);

namespace Membrane\Validator\Type;


use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class IsFloat implements Validator
{
    public function validate(mixed $value): Result
    {
        $type = gettype($value);

        if ($type !== 'double') {
            $message = new Message('Value passed to IsFloat validator is not a double, %s passed instead', [$type]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::valid($value);
    }
}
