<?php

declare(strict_types=1);

namespace Membrane\Validator\Utility;

use Membrane\Result\Result;
use Membrane\Validator;

class Indifferent implements Validator
{
    public function __toString(): string
    {
        return '';
    }

    public function __toPHP(): string
    {
        return sprintf('new %s()', self::class);
    }

    public function validate(mixed $value): Result
    {
        return Result::noResult($value);
    }
}
