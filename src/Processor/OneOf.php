<?php

declare(strict_types=1);

namespace Membrane\Processor;

use Membrane\Exception\InvalidProcessorArguments;
use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class OneOf implements Processor
{
    /** @var Processor[] */
    public array $processors;

    public function __construct(private readonly string $processes, Processor ...$processors)
    {
        if (count($processors) < 2) {
            throw InvalidProcessorArguments::redundantProcessor(OneOf::class);
        }
        $this->processors = $processors;
    }

    public function __toPHP(): string
    {
        return sprintf(
            'new %s("%s"%s)',
            self::class,
            $this->processes(),
            implode('', array_map(fn($p) => ', ' . $p->__toPHP(), $this->processors))
        );
    }

    public function __toString(): string
    {
        return "One of the following:\n\t" .
            implode(".\n\t", array_map(fn($p) => preg_replace("#\n#m", "\n\t", (string)$p), $this->processors)) . '.';
    }

    public function processes(): string
    {
        return $this->processes;
    }

    public function process(FieldName $parentFieldName, mixed $value): Result
    {
        $results = array_map(
            fn($p) => $p->process($parentFieldName, $value),
            $this->processors
        );

        if ($this->hasExactlyOneValidResult($results)) {
            return Result::valid($value);
        }

        $messageSets = [
            new MessageSet(
                $parentFieldName,
                new Message('one and only one schema must pass', [])
            ),
        ];

        foreach ($results as $result) {
            if (!$result->isValid()) {
                foreach ($result->messageSets as $messageSet) {
                    if (!$messageSet->isEmpty()) {
                        $messageSets[] = $messageSet;
                    }
                }
            }
        }

        return Result::invalid($value, ...$messageSets);
    }

    /** @param Result[] $results */
    private function hasExactlyOneValidResult(array $results): bool
    {
        return count(array_filter($results, fn($r) => $r->isValid())) === 1;
    }
}
