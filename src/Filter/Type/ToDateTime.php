<?php

declare(strict_types=1);

namespace Membrane\Filter\Type;

use DateTime;
use DateTimeImmutable;
use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class ToDateTime implements Filter
{
    public function __construct(
        private readonly string $format,
        private readonly bool $immutable = true
    ) {
    }

    public function __toString(): string
    {
        return 'convert to a DateTime';
    }

    public function __toPHP(): string
    {
        return sprintf('new %s("%s", %s)', self::class, $this->format, $this->immutable ? 'true' : 'false');
    }

    public function filter(mixed $value): Result
    {
        if (!is_string($value)) {
            $message = new Message('ToDateTime filter requires a string, %s given', [gettype($value)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        $dateTime = $this->immutable === true ?
            DateTimeImmutable::createFromFormat($this->format, $value)
            :
            DateTime::createFromFormat($this->format, $value);


        if ($dateTime === false) {
            $message = new Message(
                'String does not match the required format',
                [json_encode($this->immutable ? DateTimeImmutable::getLastErrors() : DateTime::getLastErrors())]
            );
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::noResult($dateTime);
    }
}
