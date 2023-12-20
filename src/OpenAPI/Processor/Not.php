<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Processor;

use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class Not implements Processor
{
    public function __construct(
        private readonly string $processes,
        private readonly Processor $invertedProcessor
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            'must satisfy the opposite of the following: "%s"',
            $this->invertedProcessor->__toString()
        );
    }

    public function __toPHP(): string
    {
        return sprintf(
            "new %s('%s', %s)",
            self::class,
            $this->processes,
            $this->invertedProcessor->__toPHP()
        );
    }

    public function processes(): string
    {
        return $this->processes;
    }

    public function process(FieldName $parentFieldName, mixed $value): Result
    {
        $resultToInvert = $this->invertedProcessor
            ->process($parentFieldName, $value);

        if (!$resultToInvert->isValid()) {
            return Result::valid($resultToInvert->value);
        }

        return Result::invalid(
            $resultToInvert->value,
            new MessageSet(
                $parentFieldName,
                new Message('Value is valid against a schema that MUST NOT be satisfied', [])
            )
        );
    }
}
