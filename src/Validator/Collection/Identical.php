<?php

declare(strict_types=1);

namespace Membrane\Validator\Collection;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class Identical implements Validator
{
    public function validate(mixed $value): Result
    {
        if (!is_array($value)) {
            $message = new Message('Identical Validator requires an array, %s given', [gettype($value)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        $compareTo = current($value);
        foreach ($value as $item) {
            if ($item !== $compareTo) {
                return Result::invalid($value, new MessageSet(null, new Message('Do not match', [])));
            }
        }

        return Result::valid($value);
    }
}
