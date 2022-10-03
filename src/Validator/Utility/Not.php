<?php

declare(strict_types=1);

namespace Membrane\Validator\Utility;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class Not implements Validator
{
    public function __construct(
        private Validator $invertedValidator
    ) {
    }

    public function validate(mixed $value): Result
    {
        $result = $this->invertedValidator->validate($value);
        $messageSet = new MessageSet(null, new Message('Inner validator was valid', []));

        return new Result(
            $result->value,
            $result->result * -1,
            ...($result->isValid() ? [$messageSet] : [])
        );
    }
}
