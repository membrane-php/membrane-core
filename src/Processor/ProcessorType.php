<?php

declare(strict_types=1);

namespace Membrane\Processor;

enum ProcessorType: string
{
    case Field = Field::class;
    case Fieldset = FieldSet::class;
    case Collection = Collection::class;
}
