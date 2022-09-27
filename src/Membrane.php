<?php

declare(strict_types=1);

namespace Membrane;

use Membrane\Processor\Collection;
use Membrane\Processor\Field;
use Membrane\Processor\Fieldset;
use Membrane\Result\Fieldname;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Passes;

//@TODO use this as the entry point; create builder objects; handle request routing processing etc
class Membrane
{
    public function test(): void
    {
        $f = new Fieldset(
            null,
            new Field(null, new Passes()),
            new Field('field1', new Passes()),
            new Field('field2', new Fails()),
            new Collection('collection1', new Field(null, new Passes()), new Field(null, new Fails()))
        );

        $result = $f->process(new Fieldname(''), ['field1' => 1, 'field2' => 2, 'collection1' => [1, 2, 3]]);

        var_dump($result);
    }
}
