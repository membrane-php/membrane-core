<?php

declare(strict_types=1);

namespace Membrane\Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class IsBool implements Validator
{
    public function __toString(): string
    {
        return 'is a boolean';
    }

    public function __toPHP(): string
    {
        return sprintf('new %s()', self::class);
    }

    public function validate(mixed $value): Result
    {
        $type = gettype($value);

        if ($type !== 'boolean') {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('IsBool validator expects boolean value, %s passed instead', [$type])
                )
            );
        }

        return Result::valid($value);
    }
}
