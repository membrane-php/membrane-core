<?php

declare(strict_types=1);

namespace Membrane\Validator\Utility;

use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class AllOf implements Validator
{
    /** @var Validator[] */
    private array $chain;

    public function __construct(Validator ...$chain)
    {
        $this->chain = $chain;
    }

    public function __toString(): string
    {
        $conditions = array_filter($this->chain, fn($p) => (string)$p !== '');
        if ($conditions === []) {
            return '';
        }

        return "must satisfy all of the following:\n\t" .
            implode("\n\t", array_map(fn($p) => '- ' . (string)$p . '.', $conditions));
    }

    public function __toPHP(): string
    {
        return sprintf('new %s(', self::class) .
            implode(', ', array_map(fn($p) => $p->__toPHP(), $this->chain)) .
            ')';
    }

    public function validate(mixed $value): Result
    {
        $resultChain = [];
        $mergedMessageSet = new MessageSet(null);

        foreach ($this->chain as $item) {
            $itemResult = $item->validate($value);
            if (!$itemResult->isValid()) {
                $mergedMessageSet = $mergedMessageSet->merge($itemResult->messageSets[0]);
            }
            $resultChain [] = $itemResult->result;
        }

        $result = $this->mergeResults($resultChain);

        return new Result(
            $value,
            $result,
            ...($result === Result::INVALID ? [$mergedMessageSet] : [])
        );
    }

    /** @param int[] $results */
    private function mergeResults(array $results): int
    {
        if (in_array(Result::INVALID, $results)) {
            return Result::INVALID;
        }

        if (in_array(Result::VALID, $results)) {
            return Result::VALID;
        }

        return Result::NO_RESULT;
    }
}
