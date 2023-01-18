<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Processor;

use Membrane\Exception\InvalidProcessorArguments;
use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\Result;

use function count;

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
        $results = [];
        $messageSets = [];

        foreach ($this->processors as $fieldSet) {
            $itemResult = $fieldSet->process($parentFieldName, $value);

            $results [] = $itemResult->result;

            if (!$itemResult->isValid()) {
                $messageSets [] = $itemResult->messageSets[0];
            }
        }

        $result = $this->mergeResults($results);

        return new Result(
            $value,
            $result,
            ...($result === Result::INVALID ? $messageSets : [])
        );
    }

    /** @param int[] $results */
    private function mergeResults(array $results): int
    {
        $results = array_count_values($results);

        if (isset($results[Result::VALID])) {
            if ($results[Result::VALID] === 1) {
                return Result::VALID;
            }
            return Result::INVALID;
        }

        if (isset($results[Result::INVALID])) {
            return Result::INVALID;
        }

        return Result::NO_RESULT;
    }
}
