<?php

declare(strict_types=1);

namespace Membrane\Processor;

use Membrane\Filter;
use Membrane\Processor;
use Membrane\Result\Fieldname;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class Field implements Processor
{
    private array $chain;

    public function __construct(
        private readonly string $processes,
        Filter|Validator        ...$chain
    )
    {
        $this->chain = $chain;
    }

    public function processes(): string
    {
        return $this->processes;
    }

    public function process(Fieldname $parentFieldname, mixed $value): Result
    {
        $result = Result::noResult($value);

        foreach ($this->chain as $item) {
            if ($item instanceof Validator) {
                $result = $result->merge($item->validate($result->value));
            }

            if ($item instanceof Filter) {
                $result = $result->merge($item->filter($result->value));
            }

            if (!$result->isValid()) {
                $messageSet = new MessageSet($parentFieldname->push(new Fieldname($this->processes)));

                return new Result(
                    $result->value, $result->result, $messageSet->merge(current($result->messageSets))
                );
            }
        }

        return $result;
    }
}
