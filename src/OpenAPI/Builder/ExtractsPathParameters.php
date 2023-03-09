<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

interface ExtractsPathParameters
{
    /** @return array<string, string> */
    public function getPathParams(string $requestPath): array;
}
