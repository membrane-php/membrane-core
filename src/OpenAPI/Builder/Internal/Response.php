<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder\Internal;

use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Validator\Utility\Passes;

class Response extends Schema
{
    public function build(
        V30\Response | V31\Response $response,
    ): Processor {
        return $this->fromContent($response);
    }

    private function fromContent(V30\Response | V31\Response $response): Processor
    {
        if ($response->content === []) {
            return new Field('', new Passes());
        }

        if (! isset($response->content['application/json'])) {
            throw CannotProcessOpenAPI::unsupportedMediaTypes(
                ...array_keys($response->content),
            );
        }

        if ($response->content['application/json']->schema === null) {
            return new Field('', new Passes());
        }

        return $this->fromSchema($response->content['application/json']->schema);
    }
}
