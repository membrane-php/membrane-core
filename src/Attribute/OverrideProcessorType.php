<?php

namespace Membrane\Attribute;

use Membrane\Processor\ProcessorType;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class OverrideProcessorType
{
    public function __construct(
        public readonly ProcessorType $type
    ) {
    }
}
