<?php

declare(strict_types=1);

namespace Membrane\Filter\CreateObject;

use Membrane\Exception\InvalidFilterArguments;
use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

final class CallMethod implements Filter
{
    /**
     * @param class-string $class
     */
    public function __construct(
        private readonly string $class,
        private readonly string $method
    ) {
        if (!is_callable([$this->class, $this->method])) {
            throw InvalidFilterArguments::methodNotCallable(
                $this->class,
                $this->method,
            );
        }
    }

    public function __toString(): string
    {
        return "Call $this->class::$this->method with array value as arguments";
    }

    public function __toPHP(): string
    {
        return sprintf(
            'new %s(\'%s\', \'%s\')',
            self::class,
            $this->class,
            $this->method,
        );
    }

    public function filter(mixed $value): Result
    {

        if (!is_array($value)) {
            $message = new Message(
                'CallMethod requires arrays of arguments, %s given',
                [gettype($value)],
            );
            return Result::invalid($value, new MessageSet(null, $message));
        }

        try {
            $result = call_user_func(
                sprintf('%s::%s', $this->class, $this->method),
                ...$value,
            );
        } catch (\Throwable $e) {
            return Result::invalid(
                $value,
                new MessageSet(null, new Message($e->getMessage(), [])),
            );
        }

        return Result::noResult($result);
    }
}
