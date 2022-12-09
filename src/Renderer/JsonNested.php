<?php

declare(strict_types=1);

namespace Membrane\Renderer;

use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class JsonNested implements Renderer
{
    /** @var string[][] $errors */
    private array $errors = ['errors' => [], 'fields' => []];
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
            $this->messageSetIntoArray($messageSet);
        }

        $this->parsed = true;
        return $this->errors;
    }

    public function toString(): string
    {
        return json_encode($this->toArray()) ?: '';
    }

    /** @return string[][] */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function messageSetIntoArray(MessageSet $messageSet): void
    {
        $messages = [];
        foreach ($messageSet->messages as $message) {
            $messages[] = $message->rendered();
        }

        if ($messageSet->fieldName === null) {
            $this->errors['errors'] = [...$this->errors['fields'], ...$messages];
            return;
        }

        $errorField =& $this->errors;
        $fieldNames = [...$messageSet->fieldName->stack, $messageSet->fieldName->fieldname];
        foreach ($fieldNames as $fieldName) {
            if ($fieldName === '') {
                continue;
            }

            if (!isset($errorField['fields'][$fieldName])) {
                $errorField['fields'][$fieldName] = ['errors' => [], 'fields' => []];
            }

            $errorField =& $errorField['fields'][$fieldName];
        }

        foreach ($messages as $message) {
            $errorField['errors'][] = $message;
        }
    }
}
