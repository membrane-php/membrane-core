<?php

declare(strict_types=1);

namespace Membrane\Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class IsArray implements Validator
{
    public function __toString(): string
    {
        return 'is an array with string keys';
    }

    public function __toPHP(): string
    {
        return sprintf('new %s()', self::class);
    }

    public function validate(mixed $value): Result
    {
        if (!is_array($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('IsArray validator expects array value, %s passed instead', [gettype($value)])
                )
            );
        }

        if (array_is_list($value) && $value !== []) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('IsArray validator expects array values with keys, list passed instead', [])
                )
            );
        }

        return Result::valid($value);
    }
}
