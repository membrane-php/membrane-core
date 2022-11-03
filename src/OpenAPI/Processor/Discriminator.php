<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Processor;

use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class Discriminator implements Processor
{
    public function __construct(
        private readonly string $propertyName,
        private readonly string $propertyValue,
        private readonly Processor $processor
    ) {
    }

    public function processes(): string
    {
        return $this->processor->processes();
    }

    public function process(FieldName $parentFieldName, mixed $value): Result
    {
        if (!$this->matches($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    $parentFieldName,
                    new Message(
                        '%s is expected to match %s',
                        [$this->propertyName, $this->propertyValue]
                    )
                )
            );
        }

        return $this->processor->process($parentFieldName, $value);
    }

    public function matches(mixed $value): bool
    {
        return is_array($value) && ($value[$this->propertyName] ?? null) === $this->propertyValue;
    }
}
