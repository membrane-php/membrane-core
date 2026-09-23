<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\Builder\Specification;
use Membrane\OpenAPIRouter;

final readonly class RouteMatch implements Specification
{
    public function __construct(
        public string $operationId,
        public string $source,
    ) {
    }

    public static function fromRouteMatch(OpenAPIRouter\RouteMatch $routeMatch): self
    {
        return new self(
            $routeMatch->operationId,
            $routeMatch->source,
        );
    }
}
