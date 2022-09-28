<?php

declare(strict_types=1);

namespace Membrane\Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class IsBool implements Validator
{
    public function validate(mixed $value): Result
    {
        $type = gettype($value);

        if ($type !== 'boolean') {
            $message = new Message('Value passed to IsBool validator is not a boolean, %s passed instead', [$type]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::valid($value);
    }
}
