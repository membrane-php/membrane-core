<?php

declare(strict_types=1);

namespace Membrane\Result;

class Message
{
    /** @param mixed[] $vars */
    public function __construct(
        public readonly string $message,
        public readonly array $vars
    ) {
    }

    public function rendered(): string
    {
        return vsprintf($this->message, $this->vars);
    }
}
