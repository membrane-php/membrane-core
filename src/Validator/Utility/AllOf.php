<?php

declare(strict_types=1);

namespace Membrane\Validator\Utility;

use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class AllOf implements Validator
{
    private array $chain;

    public function __construct(Validator ...$chain)
    {
        $this->chain = $chain;
    }

    public function validate(mixed $value): Result
    {
        $result = Result::noResult($value);

        foreach ($this->chain as $item) {
            $result = $this->fullMerge($result, $item->validate($value));
        }

        return $result;
    }

    public function fullMerge(Result $currentResult, Result $newResult): Result
    {
        $mergedMessageSet = new MessageSet(null);

        foreach ($currentResult->messageSets as $messageSet) {
            $mergedMessageSet = $mergedMessageSet->merge($messageSet);
        }

        foreach ($newResult->messageSets as $messageSet) {
            $mergedMessageSet = $mergedMessageSet->merge($messageSet);
        }

        return new Result(
            $newResult->value,
            $this->mergeResults($currentResult, $newResult),
            ...($mergedMessageSet->isEmpty() ? [] : [$mergedMessageSet])
        );
    }

    private function mergeResults(Result $currentResult, Result $newResult): int
    {
        if ($newResult->result === Result::NO_RESULT) {
            return $currentResult->result;
        }

        return $newResult->isValid() && $currentResult->isValid() ? Result::VALID : Result::INVALID;
    }
}
