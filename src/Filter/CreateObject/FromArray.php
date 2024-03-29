<?php

declare(strict_types=1);

namespace Membrane\Filter\CreateObject;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class FromArray implements Filter
{
    public function __construct(
        private readonly string $className
    ) {
    }

    public function __toString(): string
    {
        return sprintf('calls %s::fromArray', $this->className);
    }

    public function __toPHP(): string
    {
        return sprintf('new %s("%s")', self::class, $this->className);
    }

    public function filter(mixed $value): Result
    {
        if (!method_exists($this->className, 'fromArray')) {
            return new Result(
                $value,
                Result::INVALID,
                new MessageSet(
                    null,
                    new Message(
                        'Class (%s) doesnt have a fromArray method defined',
                        [$this->className]
                    )
                )
            );
        }

        if (!is_array($value)) {
            return new Result(
                $value,
                Result::INVALID,
                new MessageSet(
                    null,
                    new Message(
                        'Value passed to FromArray filter must be an array, %s passed instead',
                        [gettype($value)]
                    )
                )
            );
        }

        $object = $this->className::fromArray($value);

        return new Result($object, Result::NO_RESULT);
    }
}
