<?php

declare(strict_types=1);

namespace Membrane\Renderer;

use Membrane\Result\Result;

class HumanReadable implements Renderer
{
    /** @var string[][] $errors */
    private array $errors = [];
    private bool $parsed = false;

    public function __construct(
        private readonly Result $result
    ) {
    }

    /** @return string[][] */
    public function toArray(): array
    {
        if ($this->parsed || $this->result->messageSets === []) {
            return $this->errors;
        }

        foreach ($this->result->messageSets as $messageSet) {
            if ($messageSet->isEmpty()) {
                continue;
            }

            $messages = [];
            foreach ($messageSet->messages as $message) {
                $messages[] = $message->rendered();
            }

            $field = $messageSet->fieldName === null ? '' : $messageSet->fieldName->getStringRepresentation();

            if (isset($this->errors[$field])) {
                $this->errors[$field] = array_merge($this->errors[$field], $messages);
            } else {
                $this->errors[$field] = $messages;
            }
        }

        $this->parsed = true;
        return $this->errors;
    }

    public function toString(): string
    {
        $array = $this->toArray();
        ksort($array);
        $output = [];

        foreach ($array as $field => $errors) {
            $inField = false;
            if ($field !== '') {
                $output[] = $field;
                $inField = true;
            }
            foreach ($errors as $error) {
                $output[] = ($inField ? "\t- " : '- ') . $error;
            }
        }

        return implode("\n", $output);
    }

    /** @return string[][] */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
