<?php

declare(strict_types=1);

namespace Membrane\Builder;

use Membrane\Processor;

interface Builder
{
    public function supports(Specification $specification): bool;

    public function build(Specification $specification): Processor;
}
