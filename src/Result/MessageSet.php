<?php

declare(strict_types=1);

namespace Membrane\Result;

use RuntimeException;

class MessageSet
{
    /** @var array|Message[] */
    public readonly array $messages;

    public function __construct(
        public readonly ?FieldName $fieldName,
        Message ...$messages
    ) {
        $this->messages = $messages;
    }

    public function merge(MessageSet $messageSet): MessageSet
    {
        if (
            $this->fieldName?->mergable($messageSet->fieldName) === false
            ||
            $messageSet->fieldName?->mergable($this->fieldName) === false
        ) {
            throw new RuntimeException('Unable to merge message sets for different fieldNames');
        }

        return new MessageSet(
            $this->fieldName ?? $messageSet->fieldName,
            ...$this->messages,
            ...$messageSet->messages
        );
    }

    public function isEmpty(): bool
    {
        return !(isset($this->messages) && count($this->messages) > 0);
    }

    public function rendered(): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $renderedMessages = (function () {
            $renders = [];
            foreach ($this->messages as $message) {
                $renders[] = $message->rendered();
            }
            return $renders;
        })();

        if (!isset($this->fieldName)) {
            if (count($renderedMessages) === 1) {
                return $renderedMessages[0];
            } else {
                return sprintf('- %s', implode("\n- ", $renderedMessages));
            }
        }


        if (count($renderedMessages) === 1) {
            return sprintf("%s\n\t%s", $this->fieldName->getStringRepresentation(), $renderedMessages[0]);
        } else {
            return sprintf(
                "%s\n\t- %s",
                $this->fieldName->getStringRepresentation(),
                implode("\n\t- ", $renderedMessages)
            );
        }
    }
}
