<?php

declare(strict_types=1);

namespace Membrane\Validator\Logical;

use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class OneOf implements Validator
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
            if ($result->result === Result::VALID) {
                return $result;
            }
        }

        return $result;
    }

    public function fullMerge(Result $currentResult, Result $newResult): Result
    {
        $result = $this->mergeResult($currentResult, $newResult);

        $mergedMessageSet = new MessageSet(null);

        if ($result !== Result::VALID) {
            foreach ($currentResult->messageSets as $messageSet) {
                $mergedMessageSet = $mergedMessageSet->merge($messageSet);
            }

            foreach ($newResult->messageSets as $messageSet) {
                $mergedMessageSet = $mergedMessageSet->merge($messageSet);
            }
        }

        return new Result(
            $newResult->value,
            $result,
            ...($mergedMessageSet->isEmpty() ? [] : [$mergedMessageSet])
        );
    }

    private function mergeResult(Result $currentResult, Result $newResult): int
    {
        if ($newResult->result === Result::NO_RESULT && $currentResult->result === Result::NO_RESULT) {
            return Result::NO_RESULT;
        }

        return $newResult->result;
    }
}
