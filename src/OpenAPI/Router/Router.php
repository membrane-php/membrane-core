<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Router;

use Membrane\OpenAPI\Router\ValueObject\RouteCollection;


class Router
{
    public function __construct(
        private readonly RouteCollection $routeCollection
    ) {
    }

    public function route(string $url, string $method): string
    {
        if ($this->routeCollection->routes === []) {
            return '';
        }
        return '';
    }
}
