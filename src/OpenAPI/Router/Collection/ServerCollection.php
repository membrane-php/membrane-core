<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Router\Collection;

class ServerCollection
{
    /**
     * @param string[][] $operationIds
     * @param string[] $servers
     */
    public function __construct(
        public readonly array $operationIds,
        public readonly array $servers
    ) {
    }
}
