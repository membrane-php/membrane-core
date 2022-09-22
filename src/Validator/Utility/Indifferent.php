<?php

declare(strict_types=1);

namespace Membrane\Validator\Utility;

use Membrane\Result\Result;
use Membrane\Validator;

class Indifferent implements Validator
{
    public function validate(mixed $value): Result
    {
        return Result::noResult($value);
    }
}
