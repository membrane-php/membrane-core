<?php

declare(strict_types=1);

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
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('IsList validator expects list value, %s passed instead', [gettype($value)])
                )
            );
        }

        if (!array_is_list($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('IsList validator expects list value, lists do not have keys', [])
                )
            );
        }

        return Result::valid($value);
    }
}
