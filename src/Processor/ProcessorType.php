<?php

namespace Membrane\Processor;

enum ProcessorType: string
{
    case Field = Field::class;
    case Fieldset = Fieldset::class;
    case Collection = Collection::class;
}
