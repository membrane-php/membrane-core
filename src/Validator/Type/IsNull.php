<?php

declare(strict_types=1);

namespace Membrane\Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class IsNull implements Validator
{
    public function validate(mixed $value): Result
    {
        if ($value !== null) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('IsNull validator expects null value, %s passed instead', [gettype($value)])
                )
            );
        }

        return Result::valid($value);
    }
}
