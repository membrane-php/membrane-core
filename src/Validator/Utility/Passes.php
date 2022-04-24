<?php

declare(strict_types=1);

namespace Membrane\Validator\Utility;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class Passes implements Validator
{
    public function validate(mixed $value): Result
    {
        return new Result(
            $value,
            Result::VALID,
        );
    }

}