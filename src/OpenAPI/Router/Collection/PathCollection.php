<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Router\Collection;

class PathCollection
{
    /**
     * @param string[][] $operationIds
     * @param string[] $paths
     */
    public function __construct(
        public readonly array $operationIds,
        public readonly array $paths
    ) {
    }
}
