<?php

declare(strict_types=1);

namespace Membrane\Validator\Utility;

use Membrane\Result\Result;
use Membrane\Validator;

class Passes implements Validator
{
    public function validate(mixed $value): Result
    {
        return Result::valid($value);
    }
}
