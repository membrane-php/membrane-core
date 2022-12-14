<?php

declare(strict_types=1);

namespace Membrane\Renderer;

use Membrane\Result\Result;

interface Renderer extends \JsonSerializable
{
    public function toString(): string;

    /** @return array<string, array<string>> */
    public function toArray(): array;
}
