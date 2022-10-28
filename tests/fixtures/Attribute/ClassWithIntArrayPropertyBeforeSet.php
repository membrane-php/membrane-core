<?php

declare(strict_types=1);

namespace Membrane\Fixtures\Attribute;

use Membrane\Attribute\FilterOrValidator;
use Membrane\Attribute\Placement;
use Membrane\Attribute\SetFilterOrValidator;
use Membrane\Attribute\Subtype;
use Membrane\Validator\Type\IsInt;
use Membrane\Validator\Type\IsList;

class ClassWithIntArrayPropertyBeforeSet
{
    #[SetFilterOrValidator(new IsList(), Placement::BEFORE)]
    #[FilterOrValidator(new IsInt())]
    #[Subtype('int')]
    public array $arrayOfInts;
}
