<?php

namespace Membrane\Builder;

use Membrane\Processor;

interface Builder
{
    public function supports(Specification $specification): bool;

    public function build(Specification $specification): Processor;
}
