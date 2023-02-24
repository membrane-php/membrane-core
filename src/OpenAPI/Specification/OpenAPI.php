<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec as Cebe;
use Membrane\Builder\Specification;

class OpenAPI implements Specification
{
    /** @var array<string, Cebe\Server> */
    public readonly array $servers;
    /** @var array<string, Path> */
    public readonly array $paths;

    public function __construct(Cebe\OpenApi $openApi)
    {
        $this->servers = $this->getServers(...$openApi->servers);
        $this->paths = $this->getPaths(...$openApi->paths);
    }

    /** @return array<string, Cebe\Server> */
    private function getServers(Cebe\Server ...$servers): array
    {
        $serverArray = [];
        foreach ($servers as $server) {
            $serverArray[$server->url] = $server;
        }
        return $serverArray;
    }

    /** @return array<string, Path> */
    private function getPaths(Cebe\PathItem ...$paths): array
    {
        $pathArray = [];
        foreach ($paths as $relativePath => $pathItem) {
            assert(is_string($relativePath));
            $pathArray[$relativePath] = new Path($pathItem, ...$this->servers);
        }
        return $pathArray;
    }
}
