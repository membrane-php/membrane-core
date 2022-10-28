<?php

declare(strict_types=1);

namespace Membrane\Fixtures\Attribute;

use Membrane\Attribute\FilterOrValidator;
use Membrane\Attribute\OverrideProcessorType;
use Membrane\Attribute\Placement;
use Membrane\Attribute\SetFilterOrValidator;
use Membrane\Attribute\Subtype;
use Membrane\Processor\ProcessorType;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsList;

class ClassThatOverridesProcessorType
{
    #[SetFilterOrValidator(new IsList(), Placement::BEFORE)]
    #[OverrideProcessorType(ProcessorType::Collection)]
    #[Subtype('int')]
    #[FilterOrValidator(new IsInt())]
    #[SetFilterOrValidator(new ArraySumFilter(), Placement::AFTER)]
    public int $sumOfInts;
}
