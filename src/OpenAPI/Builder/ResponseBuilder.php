<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Builder\Specification;
use Membrane\OpenAPI\Specification\Response;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Validator\Utility\Passes;

class ResponseBuilder extends APIBuilder
{
    public function supports(Specification $specification): bool
    {
        return ($specification instanceof Response);
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof Response);

        return $this->fromContent($specification);
    }

    private function fromContent(Response $response): Processor
    {
        if ($response->schema === null) {
            return new Field('', new Passes());
        }

        return $this->fromSchema($response->schema);
    }
}
