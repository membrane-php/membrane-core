<?php

declare(strict_types=1);

namespace Membrane\Filter\CreateObject;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Throwable;

class WithNamedArguments implements Filter
{
    public function __construct(
        private readonly string $className
    ) {
    }

    public function __toString(): string
    {
        return sprintf('construct an instance of "%s" from named arguments contained in self', $this->className);
    }

    public function __toPHP(): string
    {
        return sprintf('new %s("%s")', self::class, $this->className);
    }

    public function filter(mixed $value): Result
    {
        try {
            $object = new $this->className(...$value);
        } catch (Throwable $t) {
            $messageSet = new MessageSet(null, new Message($t->getMessage(), []));
            return Result::invalid($value, $messageSet);
        }

        return new Result($object, Result::NO_RESULT);
    }
}
