<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Builder\Specification;
use Membrane\OpenAPI\Specification\OpenAPIResponse;
use Membrane\OpenAPI\TempHelpers\CreatesSchema;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Validator\Utility\Passes;

class OpenAPIResponseBuilder extends APIBuilder
{
    public function supports(Specification $specification): bool
    {
        return ($specification instanceof OpenAPIResponse);
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof OpenAPIResponse);

        return $this->fromContent($specification);
    }

    private function fromContent(OpenAPIResponse $response): Processor
    {
        if ($response->schema === null) {
            return new Field('', new Passes());
        }

        $schema = CreatesSchema::create($response->openAPIVersion, '', $response->schema);

        return $this->fromSchema($response->openAPIVersion, $schema);
    }
}
