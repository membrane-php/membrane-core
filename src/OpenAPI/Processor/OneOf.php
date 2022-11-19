<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Processor;

use Exception;
use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\Result;

use function count;

class OneOf implements Processor
{
    /** @var Processor[] */
    public array $fieldSets;

    public function __construct(private readonly string $processes, Processor ...$fieldSets)
    {
        if (count($fieldSets) < 2) {
            throw new Exception('AllOf Processor expects at least 2 processors');
        }
        $this->fieldSets = $fieldSets;
    }

    public function processes(): string
    {
        return $this->processes;
    }

    public function process(FieldName $parentFieldName, mixed $value): Result
    {
        $results = [];
        $messageSets = [];

        foreach ($this->fieldSets as $fieldSet) {
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
