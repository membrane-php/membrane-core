<?php

declare(strict_types=1);

namespace Membrane\Result;

class Message
{
    public function __construct(
        public readonly string $message,
        public readonly array $vars
    ) {
    }
}