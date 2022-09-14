<?php

declare(strict_types=1);

namespace Membrane\Validator\Utility;

use Membrane\Result\Result;
use Membrane\Validator;

class Chain implements Validator
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
            $result = $result->fullMerge($item->validate($value));
        }

        return $result;
    }
}