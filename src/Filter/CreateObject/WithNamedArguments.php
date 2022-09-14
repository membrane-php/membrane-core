<?php

namespace Membrane\Filter\CreateObject;

use Membrane\Filter;
use Membrane\Result\MessageSet;
use Membrane\Result\Message;
use Membrane\Result\Result;

class WithNamedArguments implements Filter
{
    public function __construct(
        private string $classname
    ) {
    }

    public function filter(mixed $value): Result
    {
        try {
            $object = new $this->classname(...$value);
        } catch (\Throwable $t) {
            $messageSet = new MessageSet(null, new Message($t->getMessage(), []));
            return Result::invalid($value, $messageSet);
        }

        return new Result($object, Result::NO_RESULT);
    }
}