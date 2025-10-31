<?php

declare(strict_types=1);

namespace Membrane\Processor;

use Membrane\Exception\InvalidProcessorArguments;
use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\Result;

class AllOf implements Processor
{
    /** @var Processor[] */
    public array $processors;

    public function __construct(private readonly string $processes, Processor ...$processors)
    {
        if (count($processors) < 2) {
            throw InvalidProcessorArguments::redundantProcessor(AllOf::class);
        }
        $this->processors = $processors;
    }

    public function __toString(): string
    {
        return "All of the following:\n\t" .
            implode(".\n\t", array_map(fn($p) => preg_replace("#\n#m", "\n\t", (string)$p), $this->processors)) . '.';
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

    public function processes(): string
    {
        return $this->processes;
    }

    public function process(FieldName $parentFieldName, mixed $value): Result
    {
        $result = Result::noResult($value);

        foreach ($this->processors as $processor) {
            $itemResult = $processor->process($parentFieldName, $value);
            $result = $result->merge($itemResult);
        }

        return $result;
    }
}
