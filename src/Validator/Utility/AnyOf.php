<?php

declare(strict_types=1);

namespace Membrane\Validator\Utility;

use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class AnyOf implements Validator
{
    /** @var Validator[] */
    private array $chain;

    public function __construct(Validator ...$chain)
    {
        $this->chain = $chain;
    }

    public function validate(mixed $value): Result
    {
        $resultChain = [];
        $messageSetChain = [];

        foreach ($this->chain as $item) {
            $itemResult = $item->validate($value);

            switch ($itemResult->result) {
                case Result::VALID:
                    return $itemResult;
                case Result::INVALID:
                    $messageSetChain [] = $itemResult->messageSets[0];
                // no break
                case Result::NO_RESULT:
                    $resultChain [] = $itemResult->result;
            }
        }

        $result = $this->mergeResult($resultChain);

        $messageSet = new MessageSet(null);
        if ($result === Result::INVALID) {
            $messageSet = $this->mergeMessages($messageSetChain);
        }

        return new Result(
            $value,
            $result,
            ...($messageSet->isEmpty() ? [] : [$messageSet])
        );
    }

    /** @param int[] $results */
    private function mergeResult(array $results): int
    {
        foreach ($results as $result) {
            if ($result === Result::INVALID) {
                return $result;
            }
        }
        return Result::NO_RESULT;
    }

    /** @param MessageSet[] $messageSets */
    private function mergeMessages(array $messageSets): MessageSet
    {
        $mergedMessageSet = new MessageSet(null);

        foreach ($messageSets as $messageSet) {
            $mergedMessageSet = $mergedMessageSet->merge($messageSet);
        }

        return $mergedMessageSet;
    }
}
