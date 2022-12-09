<?php

declare(strict_types=1);

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
