<?php

declare(strict_types=1);

namespace Membrane\Validator\FieldSet;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class RequiredFields implements Validator
{
    /** @var string[] */
    private array $fields;

    public function __construct(string ...$fields)
    {
        $this->fields = $fields;
    }

    public function validate(mixed $value): Result
    {
        if (!is_array($value)) {
            $message = new Message('RequiredFields Validator requires an array, %s given', [gettype($value)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        $messages = [];
        foreach ($this->fields as $field) {
            if (!array_key_exists($field, $value)) {
                $messages[] = new Message('%s is a required field', [$field]);
            }
        }

        if (!empty($messages)) {
            return new Result($value, Result::INVALID, new MessageSet(null, ...$messages));
        }

        return new Result($value, Result::VALID);
    }
}
