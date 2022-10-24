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
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('IsFloat expects float value, %s passed instead', [$type])
                )
            );
        }

        return Result::valid($value);
    }
}
