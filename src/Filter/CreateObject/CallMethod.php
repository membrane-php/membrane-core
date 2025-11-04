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
    /** @var callable&array{0: class-string, 1: string} */
    private readonly array $callable;

    /**
     * @param class-string $class
     */
    public function __construct(string $class, string $method)
    {
        $callable = [$class, $method];

        if (!is_callable($callable)) {
            throw InvalidFilterArguments::methodNotCallable($class, $method);
        }

        $this->callable = $callable;
    }

    public function __toString(): string
    {
        return sprintf(
            'Call %s with array value as arguments',
            implode('::', $this->callable),
        );
    }

    public function __toPHP(): string
    {
        return sprintf(
            'new %s(\'%s\', \'%s\')',
            self::class,
            $this->callable[0],
            $this->callable[1],
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
            $result = call_user_func($this->callable, ...$value);
        } catch (\Throwable $e) {
            return Result::invalid(
                $value,
                new MessageSet(null, new Message($e->getMessage(), [])),
            );
        }

        return Result::noResult($result);
    }
}
